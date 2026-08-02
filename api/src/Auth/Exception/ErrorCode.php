<?php

declare(strict_types=1);

namespace App\Auth\Exception;

use Symfony\Component\HttpFoundation\Response;

/**
 * Représente un code d'erreur de l'API.
 *
 * Cette classe encapsule le code d'erreur ainsi que les messages
 * public et technique associés.
 */
final class ErrorCode
{
    /**
     * @var array<string, array<string, array{public: string, log?: string, status?: int}>>
     */
    private static array $catalogs = [];

    /**
     * @param string $code Code d'erreur.
     * @param string $publicMessage Message destiné au client.
     * @param string|null $logMessage Message destiné aux logs.
     * @param int $statusCode Code HTTP par défaut lié au code d'erreur.
     */
    public function __construct(
        private string $code,
        private string $publicMessage,
        private ?string $logMessage = null,
        private int $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR,
    ) {}

    public static function fromCode(string $code, string $locale = 'fr-FR'): self
    {
        $catalog = self::loadCatalog($locale);
        if (!isset($catalog[$code])) {
            return new self(
                $code,
                str_starts_with($locale, 'en') ? 'Unknown error.' : 'Erreur inconnue.',
                null,
                self::defaultStatusFromCode($code),
            );
        }

        $entry = $catalog[$code];

        return new self(
            $code,
            $entry['public'],
            $entry['log'] ?? null,
            $entry['status'] ?? self::defaultStatusFromCode($code),
        );
    }

    /**
     * @return list<string>
     */
    public static function availableLocales(): array
    {
        $basePath = __DIR__ . '/../i8n';
        if (!is_dir($basePath)) {
            return ['fr-FR'];
        }

        $entries = scandir($basePath);
        if ($entries === false) {
            return ['fr-FR'];
        }

        $locales = [];
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            if (is_dir($basePath . DIRECTORY_SEPARATOR . $entry)) {
                $locales[] = $entry;
            }
        }

        if ($locales === []) {
            return ['fr-FR'];
        }

        return $locales;
    }

    /**
     * @return array<string, array{public: string, log?: string, status?: int}>
     */
    private static function loadCatalog(string $locale): array
    {
        if (isset(self::$catalogs[$locale])) {
            return self::$catalogs[$locale];
        }

        $path = __DIR__ . '/../i8n/' . $locale . '/error.php';
        if (!is_file($path)) {
            $fallbackPath = __DIR__ . '/../i8n/fr-FR/error.php';
            if (!is_file($fallbackPath)) {
                return self::$catalogs[$locale] = [];
            }

            /** @var array<string, array{public: string, log?: string, status?: int}> $fallback */
            $fallback = include $fallbackPath;

            return self::$catalogs[$locale] = $fallback;
        }

        /** @var array<string, array{public: string, log?: string, status?: int}> $catalog */
        $catalog = include $path;

        return self::$catalogs[$locale] = $catalog;
    }

    private static function defaultStatusFromCode(string $code): int
    {
        if (str_starts_with($code, 'AUTH_')) {
            return Response::HTTP_BAD_REQUEST;
        }

        if (str_starts_with($code, 'SERV_')) {
            return Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    /**
     * Retourne le code d'erreur.
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * Retourne le message destiné au client.
     */
    public function getPublicMessage(): string
    {
        return $this->publicMessage;
    }

    /**
     * @param list<string|int|float> $args
     */
    public function formatPublicMessage(array $args = []): string
    {
        if ($args === []) {
            return $this->publicMessage;
        }

        return vsprintf($this->publicMessage, $args);
    }

    /**
     * Retourne le message destiné aux logs.
     */
    public function getLogMessage(): ?string
    {
        return $this->logMessage;
    }

    /**
     * @param list<string|int|float> $args
     */
    public function formatLogMessage(array $args = []): ?string
    {
        if ($this->logMessage === null) {
            return null;
        }

        if ($args === []) {
            return $this->logMessage;
        }

        return vsprintf($this->logMessage, $args);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}