<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth\Service;

use App\Auth\Dto\ValidateRequest;
use App\Auth\Entity\ApiClient;
use App\Auth\Entity\ApiClientScope;
use App\Auth\Exception\Exception as AuthException;
use App\Auth\Repository\ApiClientScopeRepository;
use App\Auth\Service\ApiClientAuthenticator;
use App\Auth\Service\ApiClientScopeChecker;
use App\Auth\Service\ApiNonceValidator;
use App\Auth\Service\ApiRequestLogger;
use App\Auth\Service\ApiRequestValidator;
use App\Auth\Service\ApiSignatureValidator;
use App\Auth\Service\ApiTimestampValidator;
use App\Tests\Integration\Auth\Repository\AuthRepositoryTestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Clock\NativeClock;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

final class ApiRequestValidatorIntegrationTest extends AuthRepositoryTestCase
{
    public function testValidatePassesForValidRequest(): void
    {
        $client = new ApiClient('Client Valid', 'client-valid', Uuid::v4());
        $this->entityManager->persist($client);
        $this->entityManager->flush();

        $scope = new ApiClientScope($client, 'auth.validate', Uuid::v4());
        $this->entityManager->persist($scope);
        $this->entityManager->flush();
        $this->entityManager->clear();

        /** @var ApiClient $reloadedClient */
        $reloadedClient = $this->entityManager->find(ApiClient::class, $client->getId());

        $request = new ValidateRequest(
            clientId: 'client-valid',
            timestamp: time(),
            nonce: 'nonce-valid',
            signature: 'expected-signature',
            method: 'POST',
            uri: '/api/auth/validate',
            body: '{"ok":true}',
        );

        /** @var ApiClientScopeRepository $scopeRepository */
        $scopeRepository = $this->entityManager->getRepository(ApiClientScope::class);
        $service = new ApiRequestValidator(
            new ApiTimestampValidator(new NativeClock()),
            new ApiNonceValidator(),
            new ApiSignatureValidator(),
            new ApiClientAuthenticator(),
            new ApiClientScopeChecker($scopeRepository),
            new ApiRequestLogger(new NullLogger()),
        );

        $service->validate($request, $reloadedClient, 'expected-signature', 'auth.validate');

        self::assertTrue(true);
    }

    public function testValidateThrowsWhenSignatureIsInvalid(): void
    {
        $client = new ApiClient('Client Invalid Signature', 'client-invalid-signature', Uuid::v4());
        $this->entityManager->persist($client);
        $this->entityManager->flush();

        $scope = new ApiClientScope($client, 'auth.validate', Uuid::v4());
        $this->entityManager->persist($scope);
        $this->entityManager->flush();
        $this->entityManager->clear();

        /** @var ApiClient $reloadedClient */
        $reloadedClient = $this->entityManager->find(ApiClient::class, $client->getId());

        $request = new ValidateRequest(
            clientId: 'client-invalid-signature',
            timestamp: time(),
            nonce: 'nonce-valid',
            signature: 'wrong-signature',
            method: 'POST',
            uri: '/api/auth/validate',
            body: null,
        );

        /** @var ApiClientScopeRepository $scopeRepository */
        $scopeRepository = $this->entityManager->getRepository(ApiClientScope::class);
        $service = new ApiRequestValidator(
            new ApiTimestampValidator(new NativeClock()),
            new ApiNonceValidator(),
            new ApiSignatureValidator(),
            new ApiClientAuthenticator(),
            new ApiClientScopeChecker($scopeRepository),
            new ApiRequestLogger(new NullLogger()),
        );

        try {
            $service->validate($request, $reloadedClient, 'expected-signature', 'auth.validate');
            self::fail('Expected AuthException was not thrown.');
        } catch (AuthException $exception) {
            self::assertSame('AUTH_002', $exception->getErrorCode());
            self::assertSame(Response::HTTP_BAD_REQUEST, $exception->getStatusCode());
        }
    }
}
