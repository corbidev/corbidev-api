<?php

declare(strict_types=1);

namespace App\Tests\Auth\Service;

use App\Auth\Exception\Exception as AuthException;
use App\Auth\Service\ApiNonceValidator;
use PHPUnit\Framework\TestCase;

final class ApiNonceValidatorTest extends TestCase
{
    public function testValidateAcceptsNonEmptyNonce(): void
    {
        $validator = new ApiNonceValidator();

        $validator->validate('nonce-123');

        self::assertTrue(true);
    }

    public function testValidateThrowsForEmptyNonce(): void
    {
        $validator = new ApiNonceValidator();

        try {
            $validator->validate('   ');
            self::fail('Expected AuthException was not thrown.');
        } catch (AuthException $exception) {
            self::assertSame('AUTH_005', $exception->getErrorCode());
            self::assertNull($exception->getStatusCode());
        }
    }
}
