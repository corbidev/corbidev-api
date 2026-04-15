<?php

namespace App\Logs\Context;

use App\Logs\Repository\LogDomainRepository;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Charge et met en cache la liste des domaines autorises pour le scoping multi-tenant.
 */
class TenantProvider
{
    private const CACHE_KEY = 'tenant_domains';

    public function __construct(
        private LogDomainRepository $domainRepository,
        private CacheItemPoolInterface $cache
    ) {}

    /**
     * Retourne la liste normalisee des domaines autorises.
     *
     * @return list<string>
     */
    public function getAllowedDomains(): array
    {
        $cacheItem = $this->cache->getItem(self::CACHE_KEY);

        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }

        // La liste complete est chargee une fois puis servie depuis le cache.
        $domains = $this->domainRepository->findAll();

        $urls = array_map(
            fn($d) => strtolower(rtrim($d->getUrl(), '/')),
            $domains
        );

        // Le TTL est volontairement court pour absorber les modifications de referentiel.
        $cacheItem->set($urls);
        $cacheItem->expiresAfter(600);

        $this->cache->save($cacheItem);

        return $urls;
    }
}