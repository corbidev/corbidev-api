<?php

namespace App\RessLogs\Security;

use Symfony\Component\HttpFoundation\Request;

interface LogConsumerJwtResolverInterface
{
    public function resolveSourceApiKeyFromRequest(Request $request): ?string;
}
