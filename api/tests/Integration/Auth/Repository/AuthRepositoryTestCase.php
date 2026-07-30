<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class AuthRepositoryTestCase extends KernelTestCase
{
    protected EntityManagerInterface $entityManager;

    protected static function getKernelClass(): string
    {
        return 'App\\Kernel';
    }

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = self::getContainer()->get('doctrine')->getManager('auth');
        $this->resetAuthSchema();
    }

    protected function tearDown(): void
    {
        $this->entityManager->clear();
        $this->entityManager->getConnection()->close();

        self::ensureKernelShutdown();

        parent::tearDown();
    }

    protected function setCreatedAt(object $entity, \DateTimeImmutable $createdAt): void
    {
        $property = new \ReflectionProperty($entity, 'createdAt');
        $property->setValue($entity, $createdAt);
    }

    private function resetAuthSchema(): void
    {
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->entityManager);

        if ($metadata === []) {
            return;
        }

        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }
}