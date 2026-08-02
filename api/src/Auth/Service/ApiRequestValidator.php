<?php

declare(strict_types=1);

namespace App\Auth\Service;

use App\Auth\Dto\ValidateRequest;
use App\Auth\Entity\ApiClient;

/**
 * Orchestre les différentes validations d'une requête HMAC.
 */
final readonly class ApiRequestValidator
{
    public function __construct(
        private ApiTimestampValidator $timestampValidator,
        private ApiNonceValidator $nonceValidator,
        private ApiSignatureValidator $signatureValidator,
        private ApiClientAuthenticator $clientAuthenticator,
        private ApiClientScopeChecker $scopeChecker,
        private ApiRequestLogger $requestLogger,
    ) {}

    /**
     * Valide une requête HMAC.
     *
     * @param ValidateRequest $request Requête reçue.
     * @param ApiClient $client Client API.
     * @param string $expectedSignature Signature calculée.
     * @param string $requiredScope Scope attendu.
     */
    public function validate(
        ValidateRequest $request,
        ApiClient $client,
        string $expectedSignature,
        string $requiredScope,
    ): void {
        $this->timestampValidator->validate($request->timestamp);
        $this->nonceValidator->validate($request->nonce);

        $this->clientAuthenticator->authenticate($client);

        $this->scopeChecker->check(
            $client,
            $requiredScope,
        );

        $this->signatureValidator->validate(
            $expectedSignature,
            $request->signature,
        );

        $this->requestLogger->log($request);
    }
}