<?php

namespace App\Http;

class HttpStatusProvider
{
    private array $statuses;

    public function __construct(string $filePath)
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("HTTP status file not found");
        }

        $this->statuses = json_decode(file_get_contents($filePath), true);
    }

    public function get(int $status): array
    {
        return $this->statuses[$status] ?? [
            'message' => 'Erreur inconnue',
            'description' => 'Une erreur inattendue est survenue.'
        ];
    }
}