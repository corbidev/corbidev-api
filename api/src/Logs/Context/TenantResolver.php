<?php

namespace App\Logs\Context;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use App\Logs\Context\TenantContext;

/**
 * Resout le tenant courant a partir des en-tetes HTTP et verifie son autorisation.
 */
class TenantResolver
{
    public function __construct(
        private RequestStack $requestStack,
        private TenantProvider $tenantProvider
    ) {}

    /**
     * Construit le contexte tenant de la requete courante.
     *
     * @throws \RuntimeException Si aucune requete HTTP n'est disponible.
     * @throws UnauthorizedHttpException Si le domaine n'est pas autorise.
     */
    public function resolve(): TenantContext
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            throw new \RuntimeException('No request');
        }

        $domain = $request->headers->get('X-Tenant-Domain')
            ?? $request->headers->get('Origin')
            ?? $request->getSchemeAndHttpHost();

        $client = $request->headers->get('X-Tenant-Client');
        $version = $request->headers->get('X-Tenant-Version');

        $domain = $this->normalize($domain);

        // La validation s'appuie sur le referentiel cache pour eviter des acces repetes a la base.
        $allowed = $this->tenantProvider->getAllowedDomains();

        if (!in_array($domain, $allowed, true)) {
            throw new UnauthorizedHttpException('', 'Invalid tenant');
        }

        return new TenantContext($domain, $client, $version);
    }

    /**
     * Uniformise le format du domaine pour les comparaisons de scope.
     */
    private function normalize(string $domain): string
    {
        return strtolower(rtrim($domain, '/'));
    }
}
