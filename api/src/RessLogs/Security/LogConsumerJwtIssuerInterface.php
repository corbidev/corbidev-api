<?php

namespace App\RessLogs\Security;

interface LogConsumerJwtIssuerInterface
{
    /**
     * @return array{accessToken: string, expiresIn: int}
     */
    public function issueFromCredentials(string $sourceApiKey, string $clientSecret): array;
}
