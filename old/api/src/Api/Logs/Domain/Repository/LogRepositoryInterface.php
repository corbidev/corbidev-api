<?php

declare(strict_types=1);

namespace App\Api\Logs\Domain\Repository;

interface LogRepositoryInterface
{
    public function existsByExternalId(string $externalId): bool;
}