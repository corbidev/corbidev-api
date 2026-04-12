<?php

namespace App\RessAuth\Security;

use App\RessAuth\Dto\AccessTokenPayload;

interface AccessTokenIssuerInterface
{
    public function issueFromCredentials(string $sourceApiKey, string $clientSecret): AccessTokenPayload;
}