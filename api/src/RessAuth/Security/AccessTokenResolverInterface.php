<?php

namespace App\RessAuth\Security;

use App\RessAuth\Dto\AccessTokenContext;
use Symfony\Component\HttpFoundation\Request;

interface AccessTokenResolverInterface
{
    public function verifyRequest(Request $request): AccessTokenContext;

    public function requireRequest(Request $request): AccessTokenContext;

    public function resolveSourceApiKeyFromRequest(Request $request): ?string;
}