<?php

namespace App\RessLogs\Service;

use App\RessLogs\Dto\CreateLogRequestDto;
use App\RessLogs\Entity\LogEntry;
use App\RessLogs\Entity\LogEntryTag;
use App\RessLogs\Entity\LogEnv;
use App\RessLogs\Entity\LogLevel;
use App\RessLogs\Entity\LogSource;
use App\RessLogs\Entity\LogTag;
use App\RessLogs\Entity\LogUri;
use App\RessLogs\Entity\LogUrl;
use App\RessLogs\Repository\LogEnvRepository;
use App\RessLogs\Repository\LogLevelRepository;
use App\RessLogs\Repository\LogSourceRepository;
use App\RessLogs\Repository\LogTagRepository;
use App\RessLogs\Repository\LogUriRepository;
use App\RessLogs\Repository\LogUrlRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

class LogRecorder implements LogRecorderInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LogLevelRepository $logLevelRepository,
        private readonly LogEnvRepository $logEnvRepository,
        private readonly LogSourceRepository $logSourceRepository,
        private readonly LogUriRepository $logUriRepository,
        private readonly LogUrlRepository $logUrlRepository,
        private readonly LogTagRepository $logTagRepository,
    ) {
    }

    public function record(CreateLogRequestDto $request): LogEntry
    {
        $message = trim($request->message);
        if ($message === '') {
            throw new InvalidArgumentException('Le champ «message» est obligatoire.');
        }

        $urlValue = $this->nullableString($request->url);
        if ($urlValue === null) {
            throw new InvalidArgumentException('Le champ «url» est obligatoire.');
        }

        if (!$this->isStandardUrl($urlValue)) {
            throw new InvalidArgumentException('Le champ «url» doit être une URL valide (http/https).');
        }

        $level = $this->resolveLevel($request->level);
        $env = $this->resolveEnv($request->env);
        $source = $this->resolveSource($request);
        [$url, $uri] = $this->resolveUrlAndUri($request, $urlValue);

        $entry = new LogEntry();
        $entry->setMessage($message);
        $entry->setLevel($level);
        $entry->setEnv($env);
        $entry->setSource($source);
        $entry->setUrl($url);
        $entry->setUri($uri);
        $entry->setTs($this->toDateTimeImmutable($request->ts) ?? new DateTimeImmutable());
        $entry->setCreatedAt($this->toDateTimeImmutable($request->createdAt) ?? new DateTimeImmutable());

        if ($request->title !== null) {
            $entry->setTitle($request->title);
        }

        if ($request->httpStatus !== null) {
            $entry->setHttpStatus($request->httpStatus);
        }

        if ($request->durationMs !== null) {
            $entry->setDurationMs($request->durationMs);
        }

        if ($request->fingerprint !== null) {
            $entry->setFingerprint($request->fingerprint);
        }

        if ($request->context !== null) {
            $entry->setContext($request->context);
        }

        $this->attachTags($entry, $request->tags);

        $this->entityManager->persist($entry);
        $this->entityManager->flush();
        $this->logUriRepository->deleteOrphans();
        $this->logUrlRepository->deleteOrphans();

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

    private function resolveSource(CreateLogRequestDto $request): LogSource
    {
        $sourceId = $request->sourceId;
        $sourceApiKey = $request->sourceApiKey;

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

        throw new InvalidArgumentException('Source introuvable. Fournissez «sourceId» ou «sourceApiKey» valide.');
    }

    private function resolveUrlAndUri(CreateLogRequestDto $request, string $validatedUrl): array
    {
        $urlId = $request->urlId;
        $uriId = $request->uriId ?? $request->routeId;

        $urlValue = $validatedUrl;
        $uriValue = $request->uri ?? $request->routeUri;

        [$urlValue, $uriValue] = $this->normalizeUrlAndUriValues($urlValue, $uriValue);

        $url = null;
        if ($urlId !== null) {
            $url = $this->logUrlRepository->find((int) $urlId);
            if (!$url instanceof LogUrl) {
                throw new InvalidArgumentException(sprintf('URL introuvable pour l\'id %s.', (string) $urlId));
            }
        }

        if ($url === null && $urlValue !== null) {
            $url = $this->logUrlRepository->findOneBy(['url' => $urlValue]);
            if (!$url instanceof LogUrl) {
                $url = new LogUrl();
                $url->setUrl($urlValue);
                $this->entityManager->persist($url);
            }
        }

        $uri = null;
        if ($uriId !== null) {
            $uri = $this->logUriRepository->find((int) $uriId);
            if (!$uri instanceof LogUri) {
                throw new InvalidArgumentException(sprintf('URI introuvable pour l\'id %s.', (string) $uriId));
            }
        }

        if ($uri === null && $uriValue !== null) {
            $uri = $this->logUriRepository->findOneBy(['uri' => $uriValue]);
            if (!$uri instanceof LogUri) {
                $uri = new LogUri();
                $uri->setUri($uriValue);
                $this->entityManager->persist($uri);
            }
        }

        if ($uri instanceof LogUri && $uri->getUrl() instanceof LogUrl) {
            if ($url instanceof LogUrl && $uri->getUrl()->getId() !== $url->getId()) {
                throw new InvalidArgumentException('Incoherence entre URL et URI: cette URI est deja rattachée a une autre URL.');
            }

            if (!$url instanceof LogUrl) {
                $url = $uri->getUrl();
            }
        }

        if ($uri instanceof LogUri && !$url instanceof LogUrl) {
            throw new InvalidArgumentException('Une URI doit être rattachée à une URL. Fournissez "url"/"routeUrl" ou "urlId".');
        }

        if ($uri instanceof LogUri && $url instanceof LogUrl && $uri->getUrl() === null) {
            $uri->setUrl($url);
        }

        return [$url, $uri];
    }

    private function normalizeUrlAndUriValues(?string $urlValue, ?string $uriValue): array
    {
        if ($urlValue === null) {
            return [$urlValue, $uriValue];
        }

        $extractedUri = $this->extractUriFromUrlValue($urlValue);

        if ($uriValue === null && $extractedUri !== null) {
            $uriValue = $extractedUri;
        }

        if ($uriValue !== null && $extractedUri !== null) {
            $urlValue = $this->stripUriFromUrlValue($urlValue);
        }

        return [$urlValue, $uriValue];
    }

    private function extractUriFromUrlValue(string $urlValue): ?string
    {
        if (str_starts_with($urlValue, '/')) {
            return $urlValue;
        }

        if (!filter_var($urlValue, FILTER_VALIDATE_URL)) {
            return null;
        }

        $path = parse_url($urlValue, PHP_URL_PATH);
        if (!is_string($path) || $path === '' || $path === '/') {
            return null;
        }

        return $path;
    }

    private function stripUriFromUrlValue(string $urlValue): ?string
    {
        if (str_starts_with($urlValue, '/')) {
            return null;
        }

        if (!filter_var($urlValue, FILTER_VALIDATE_URL)) {
            return $urlValue;
        }

        $parts = parse_url($urlValue);
        if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
            return $urlValue;
        }

        $normalized = sprintf('%s://', $parts['scheme']);

        if (isset($parts['user'])) {
            $normalized .= $parts['user'];
            if (isset($parts['pass'])) {
                $normalized .= ':' . $parts['pass'];
            }
            $normalized .= '@';
        }

        $normalized .= $parts['host'];

        if (isset($parts['port'])) {
            $normalized .= ':' . $parts['port'];
        }

        return $normalized;
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

    private function isStandardUrl(string $url): bool
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $parts = parse_url($url);
        if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
            return false;
        }

        return in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true);
    }
}
