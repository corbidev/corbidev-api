<?php

namespace App\RessLogs\Security;

use Symfony\Component\HttpFoundation\Request;

interface LogConsumerJwtResolverInterface
{
    /**
     * @return array{sourceApiKey: ?string, sourceId: ?int}
     */
    public function resolveSourceContextFromRequest(Request $request): array;

    public function resolveSourceApiKeyFromRequest(Request $request): ?string;
}
