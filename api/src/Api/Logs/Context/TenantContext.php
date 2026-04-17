<?php

namespace App\Api\Logs\Context;

/**
 * Represente le contexte tenant resolu depuis la requete courante.
 */
class TenantContext
{
    /**
     * @param string $domain Domaine normalise du tenant.
     * @param string|null $client Client applicatif source.
     * @param string|null $version Version applicative source.
     */
    public function __construct(
        public readonly string $domain,
        public readonly ?string $client = null,
        public readonly ?string $version = null,
    ) {}
}