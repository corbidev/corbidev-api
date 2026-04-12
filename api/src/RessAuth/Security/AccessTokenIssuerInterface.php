<?php

namespace App\RessAuth\Security;

interface AccessTokenIssuerInterface
{
    /**
     * @return array{accessToken: string, expiresIn: int}
     */
    public function issueFromCredentials(string $sourceApiKey, string $clientSecret): array;
}