<?php

namespace App\RessLogs\Service;

use App\RessLogs\Entity\LogEntry;
use App\RessLogs\Entity\LogEntryTag;
use App\RessLogs\Entity\LogEnv;
use App\RessLogs\Entity\LogLevel;
use App\RessLogs\Entity\LogRoute;
use App\RessLogs\Entity\LogSource;
use App\RessLogs\Entity\LogTag;
use App\RessLogs\Repository\LogEnvRepository;
use App\RessLogs\Repository\LogLevelRepository;
use App\RessLogs\Repository\LogRouteRepository;
use App\RessLogs\Repository\LogSourceRepository;
use App\RessLogs\Repository\LogTagRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

class LogRecorder
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LogLevelRepository $logLevelRepository,
        private readonly LogEnvRepository $logEnvRepository,
        private readonly LogSourceRepository $logSourceRepository,
        private readonly LogRouteRepository $logRouteRepository,
        private readonly LogTagRepository $logTagRepository,
    ) {
    }

    /**
     * @param array{
     *     message: string,
     *     title?: string|null,
     *     url?: string|null,
     *     httpStatus?: int|null,
     *     durationMs?: int|null,
     *     fingerprint?: string|null,
     *     context?: array|null,
     *     ts?: DateTimeImmutable|string|null,
     *     createdAt?: DateTimeImmutable|string|null,
     *     level?: int|string|null,
     *     env?: int|string|null,
     *     sourceId?: int|null,
     *     sourceApiKey?: string|null,
     *     routeId?: int|null,
    *     routeUrl?: string|null,
     *     routeUri?: string|null,
     *     tags?: array<int|string>|null
     * } $payload
     */
    public function record(array $payload): LogEntry
    {
        $message = trim((string) ($payload['message'] ?? ''));
        if ($message === '') {
            throw new InvalidArgumentException('Le champ "message" est obligatoire.');
        }

        $level = $this->resolveLevel($payload['level'] ?? null);
        $env = $this->resolveEnv($payload['env'] ?? null);
        $source = $this->resolveSource($payload);
        $route = $this->resolveRoute($payload);

        $entry = new LogEntry();
        $entry->setMessage($message);
        $entry->setLevel($level);
        $entry->setEnv($env);
        $entry->setSource($source);
        $entry->setRoute($route);
        $entry->setTs($this->toDateTimeImmutable($payload['ts'] ?? null) ?? new DateTimeImmutable());
        $entry->setCreatedAt($this->toDateTimeImmutable($payload['createdAt'] ?? null) ?? new DateTimeImmutable());

        if (array_key_exists('title', $payload)) {
            $entry->setTitle($this->nullableString($payload['title']));
        }

        if (array_key_exists('url', $payload)) {
            $entry->setUrl($this->nullableString($payload['url']));
        }

        if (array_key_exists('httpStatus', $payload)) {
            $entry->setHttpStatus($payload['httpStatus'] !== null ? (int) $payload['httpStatus'] : null);
        }

        if (array_key_exists('durationMs', $payload)) {
            $entry->setDurationMs($payload['durationMs'] !== null ? (int) $payload['durationMs'] : null);
        }

        if (array_key_exists('fingerprint', $payload)) {
            $entry->setFingerprint($this->nullableString($payload['fingerprint']));
        }

        if (array_key_exists('context', $payload)) {
            $entry->setContext(is_array($payload['context']) ? $payload['context'] : null);
        }

        $this->attachTags($entry, $payload['tags'] ?? null);

        $this->entityManager->persist($entry);
        $this->entityManager->flush();

        return $entry;
    }

    private function resolveLevel(int|string|null $level): LogLevel
    {
        if ($level === null) {
            $level = 200;
        }

        $entity = is_int($level)
            ? $this->logLevelRepository->find($level)
            : $this->logLevelRepository->findOneBy(['name' => (string) $level]);

        if (!$entity instanceof LogLevel) {
            throw new InvalidArgumentException(sprintf('Niveau de log introuvable: %s', (string) $level));
        }

        return $entity;
    }

    private function resolveEnv(int|string|null $env): LogEnv
    {
        if ($env === null) {
            $env = 1;
        }

        $entity = is_int($env)
            ? $this->logEnvRepository->find($env)
            : $this->logEnvRepository->findOneBy(['name' => (string) $env]);

        if (!$entity instanceof LogEnv) {
            throw new InvalidArgumentException(sprintf('Environnement introuvable: %s', (string) $env));
        }

        return $entity;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function resolveSource(array $payload): LogSource
    {
        $sourceId = $payload['sourceId'] ?? null;
        $sourceApiKey = $payload['sourceApiKey'] ?? null;

        if ($sourceId !== null) {
            $source = $this->logSourceRepository->find((int) $sourceId);
            if ($source instanceof LogSource) {
                return $source;
            }
        }

        if (is_string($sourceApiKey) && $sourceApiKey !== '') {
            $source = $this->logSourceRepository->findOneBy(['apiKey' => $sourceApiKey, 'isActive' => true]);
            if ($source instanceof LogSource) {
                return $source;
            }
        }

        throw new InvalidArgumentException('Source introuvable. Fournissez "sourceId" ou "sourceApiKey" valide.');
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function resolveRoute(array $payload): ?LogRoute
    {
        $routeId = $payload['routeId'] ?? null;
        $routeUri = $this->nullableString($payload['routeUri'] ?? null);

        if ($routeId !== null) {
            $route = $this->logRouteRepository->find((int) $routeId);
            if (!$route instanceof LogRoute) {
                throw new InvalidArgumentException(sprintf('Route introuvable pour l\'id %s.', (string) $routeId));
            }

            return $route;
        }

        if ($routeUri === null) {
            return null;
        }

        $route = $this->logRouteRepository->findOneBy(['uri' => $routeUri]);
        if ($route instanceof LogRoute) {
            return $route;
        }

        $newRoute = new LogRoute();
        $newRoute->setUri($routeUri);
        $this->entityManager->persist($newRoute);

        return $newRoute;
    }

    /**
     * @param array<int|string>|null $tags
     */
    private function attachTags(LogEntry $entry, ?array $tags): void
    {
        if ($tags === null || $tags === []) {
            return;
        }

        $processed = [];

        foreach ($tags as $tagValue) {
            $key = is_int($tagValue) ? sprintf('id:%d', $tagValue) : sprintf('name:%s', mb_strtolower(trim((string) $tagValue)));
            if (isset($processed[$key])) {
                continue;
            }

            $processed[$key] = true;

            $tag = $this->resolveTag($tagValue);
            $entryTag = new LogEntryTag();
            $entryTag->setLogEntry($entry);
            $entryTag->setTag($tag);
            $entry->addEntryTag($entryTag);
            $this->entityManager->persist($entryTag);
        }
    }

    private function resolveTag(int|string $tagValue): LogTag
    {
        $tag = is_int($tagValue)
            ? $this->logTagRepository->find($tagValue)
            : $this->logTagRepository->findOneBy(['name' => trim((string) $tagValue)]);

        if ($tag instanceof LogTag) {
            return $tag;
        }

        if (is_int($tagValue)) {
            throw new InvalidArgumentException(sprintf('Tag introuvable pour l\'id %d.', $tagValue));
        }

        $tagName = trim((string) $tagValue);
        if ($tagName === '') {
            throw new InvalidArgumentException('Un tag vide ne peut pas être enregistré.');
        }

        $newTag = new LogTag();
        $newTag->setName($tagName);
        $this->entityManager->persist($newTag);

        return $newTag;
    }

    private function toDateTimeImmutable(DateTimeImmutable|string|null $value): ?DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeImmutable) {
            return $value;
        }

        $stringValue = trim($value);
        if ($stringValue === '') {
            return null;
        }

        return new DateTimeImmutable($stringValue);
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $stringValue = trim((string) $value);

        return $stringValue === '' ? null : $stringValue;
    }
}
