<?php

declare(strict_types=1);

namespace App\Tests\Auth\Service;

use App\Auth\Service\ApiSecretHasher;
use PHPUnit\Framework\TestCase;

/**
 * Vérifie le hachage et la validation des secrets API.
 */
final class ApiSecretHasherTest extends TestCase
{
    private ApiSecretHasher $hasher;

    protected function setUp(): void
    {
        $this->hasher = new ApiSecretHasher();
    }

    /**
     * Un secret haché doit être différent de sa valeur d'origine.
     */
    public function testHashReturnsHashedValue(): void
    {
        $secret = 'my-super-secret';

        $hash = $this->hasher->hash($secret);

        self::assertNotSame($secret, $hash);
        self::assertNotEmpty($hash);
    }

    /**
     * Deux hachages d'un même secret doivent être différents
     * (salt aléatoire de password_hash()).
     */
    public function testHashGeneratesDifferentHashes(): void
    {
        $secret = 'my-super-secret';

        $hash1 = $this->hasher->hash($secret);
        $hash2 = $this->hasher->hash($secret);

        self::assertNotSame($hash1, $hash2);
    }

    /**
     * Un secret valide doit être reconnu.
     */
    public function testVerifyReturnsTrueForValidSecret(): void
    {
        $secret = 'my-super-secret';

        $hash = $this->hasher->hash($secret);

        self::assertTrue(
            $this->hasher->verify($secret, $hash)
        );
    }

    /**
     * Un secret incorrect doit être refusé.
     */
    public function testVerifyReturnsFalseForInvalidSecret(): void
    {
        $hash = $this->hasher->hash('my-super-secret');

        self::assertFalse(
            $this->hasher->verify('wrong-secret', $hash)
        );
    }

    /**
     * Un hash invalide doit être refusé.
     */
    public function testVerifyReturnsFalseForInvalidHash(): void
    {
        self::assertFalse(
            $this->hasher->verify('my-super-secret', 'invalid-hash')
        );
    }

    /**
     * Un secret vide ne doit pas correspondre à un hash valide.
     */
    public function testVerifyReturnsFalseForEmptySecret(): void
    {
        $hash = $this->hasher->hash('my-super-secret');

        self::assertFalse(
            $this->hasher->verify('', $hash)
        );
    }
}