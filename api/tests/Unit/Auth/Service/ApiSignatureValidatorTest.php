<?php

declare(strict_types=1);

namespace App\Tests\Auth\Service;

use App\Auth\Exception\Exception as AuthException;
use App\Auth\Service\ApiSignatureValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

final class ApiSignatureValidatorTest extends TestCase
{
    public function testValidateAcceptsMatchingSignature(): void
    {
        $validator = new ApiSignatureValidator();

        $validator->validate('abc123', 'abc123');

        self::assertTrue(true);
    }

    public function testValidateThrowsForMismatchedSignature(): void
    {
        $validator = new ApiSignatureValidator();

        try {
            $validator->validate('expected-signature', 'received-signature');
            self::fail('Expected AuthException was not thrown.');
        } catch (AuthException $exception) {
            self::assertSame('AUTH_002', $exception->getErrorCode());
            self::assertSame(Response::HTTP_BAD_REQUEST, $exception->getStatusCode());
        }
    }
}
