<?php

declare(strict_types=1);

namespace App\Api\Logs\Domain\Service;

use App\Api\Logs\Domain\Repository\LogRepositoryInterface;
use App\Shared\Domain\Error\DomainException;
use App\Shared\Domain\Error\ErrorCode;
use App\Shared\Domain\Error\BusinessErrorCode;

final class LogValidator
{
    public function __construct(
        private readonly LogRepositoryInterface $repository
    ) {}

    public function validateUniqueExternalId(string $externalId): void
    {
        if ($this->repository->existsByExternalId($externalId)) {
            throw new DomainException(
                errorCode: ErrorCode::RESOURCE_ALREADY_EXISTS,
                message: 'Log already exists',
                details: [
                    'externalId' => [
                        [
                            'code' => 'ALREADY_EXISTS',
                            'message' => 'This externalId already exists'
                        ]
                    ]
                ],
                businessCode: BusinessErrorCode::LOG_ALREADY_EXISTS
            );
        }
    }
}