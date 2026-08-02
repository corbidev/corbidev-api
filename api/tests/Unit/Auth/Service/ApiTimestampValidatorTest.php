<?php

declare(strict_types=1);

namespace App\Tests\Auth\Service;

use App\Auth\Exception\Exception as AuthException;
use App\Auth\Service\ApiTimestampValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\ClockInterface;

final class ApiTimestampValidatorTest extends TestCase
{
    public function testValidateAcceptsTimestampWithinAllowedDrift(): void
    {
        $clock = $this->createStub(ClockInterface::class);
        $clock->method('now')->willReturn(new \DateTimeImmutable('@1000'));

        $validator = new ApiTimestampValidator($clock, 300);

        $validator->validate(800);

        self::assertTrue(true);
    }

    public function testValidateThrowsWhenTimestampIsOutsideAllowedDrift(): void
    {
        $clock = $this->createStub(ClockInterface::class);
        $clock->method('now')->willReturn(new \DateTimeImmutable('@1000'));

        $validator = new ApiTimestampValidator($clock, 300);

        try {
            $validator->validate(600);
            self::fail('Expected AuthException was not thrown.');
        } catch (AuthException $exception) {
            self::assertSame('AUTH_003', $exception->getErrorCode());
            self::assertNull($exception->getStatusCode());
        }
    }
}
