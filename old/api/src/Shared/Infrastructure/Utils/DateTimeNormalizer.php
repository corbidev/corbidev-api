<?php

namespace App\Shared\Infrastructure\Util;

final class DateTimeNormalizer
{
    private const UTC = 'UTC';

    /**
     * 🕒 Maintenant en UTC
     */
    public static function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone(self::UTC));
    }

    /**
     * 🧠 Convertit n'importe quelle date en UTC
     */
    public static function toUtc(\DateTimeInterface $date): \DateTimeImmutable
    {
        return (new \DateTimeImmutable($date->format('Y-m-d H:i:s.u'), $date->getTimezone()))
            ->setTimezone(new \DateTimeZone(self::UTC));
    }

    /**
     * 🔐 Parse string → UTC (safe)
     */
    public static function fromString(?string $value, ?\DateTimeImmutable $fallback = null): \DateTimeImmutable
    {
        if (!$value) {
            return $fallback ?? self::now();
        }

        try {
            $date = new \DateTimeImmutable($value);
            return self::toUtc($date);
        } catch (\Throwable) {
            return $fallback ?? self::now();
        }
    }

    /**
     * 🧾 Timestamp UNIX → UTC
     */
    public static function fromTimestamp(int $timestamp): \DateTimeImmutable
    {
        return (new \DateTimeImmutable('@' . $timestamp))
            ->setTimezone(new \DateTimeZone(self::UTC));
    }

    /**
     * 📁 Date depuis fichier (mtime)
     */
    public static function fromFile(string $file, ?\DateTimeImmutable $fallback = null): \DateTimeImmutable
    {
        if (!is_file($file)) {
            return $fallback ?? self::now();
        }

        $mtime = filemtime($file);

        if ($mtime === false) {
            return $fallback ?? self::now();
        }

        return self::fromTimestamp($mtime);
    }

    /**
     * 🧬 Parse depuis nom de fichier queue (optionnel avancé)
     * Format attendu :
     * queue_YYYY-MM-DD_HH-MM-SS_micro.log
     */
    public static function fromFilename(string $file, ?\DateTimeImmutable $fallback = null): \DateTimeImmutable
    {
        $basename = basename($file);

        if (preg_match('/queue_(\d{4}-\d{2}-\d{2})_(\d{2}-\d{2}-\d{2})_(\d+)/', $basename, $matches)) {

            $date = str_replace('-', ':', $matches[2]); // HH-MM-SS → HH:MM:SS

            $dateString = sprintf(
                '%s %s.%s',
                $matches[1],
                $date,
                $matches[3]
            );

            try {
                return new \DateTimeImmutable($dateString, new \DateTimeZone(self::UTC));
            } catch (\Throwable) {
                return $fallback ?? self::now();
            }
        }

        return $fallback ?? self::now();
    }

    /**
     * 🧠 Méthode principale intelligente
     */
    public static function resolve(
        ?string $clientTimestamp,
        ?string $file = null
    ): \DateTimeImmutable {
        // 1. client
        if ($clientTimestamp) {
            try {
                return self::fromString($clientTimestamp);
            } catch (\Throwable) {
                // ignore → fallback
            }
        }

        // 2. fichier (nom)
        if ($file) {
            return self::fromFilename(
                $file,
                self::fromFile($file)
            );
        }

        // 3. fallback ultime
        return self::now();
    }
}