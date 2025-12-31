<?php

/**
 * Entity lifecycle tests.
 *
 * @package   Fabryq\Tests
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Tests;

use DateTimeImmutable;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Fabryq\Tests\Fixtures\Entity\TestEntity;
use PHPUnit\Framework\TestCase;

final class EntityLifecycleTest extends TestCase
{
    private EntityManager $entityManager;

    public function testCreatedAndUpdatedSetOnPersist(): void
    {
        $entity = new TestEntity();
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $this->assertMatchesRegularExpression('/^[0-9A-HJKMNP-TV-Z]{26}$/', $entity->getId());
        $this->assertInstanceOf(DateTimeImmutable::class, $entity->getCreatedAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $entity->getUpdatedAt());
        $this->assertGreaterThanOrEqual(
            $entity->getCreatedAt()->getTimestamp(),
            $entity->getUpdatedAt()?->getTimestamp() ?? 0
        );
    }

    public function testUpdatedAtChangesOnUpdate(): void
    {
        $entity = new TestEntity();
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $previous = $entity->getUpdatedAt();
        $this->assertInstanceOf(DateTimeImmutable::class, $previous);

        usleep(1100000);
        $entity->archive();
        $this->entityManager->flush();

        $this->assertNotNull($entity->getUpdatedAt());
        $this->assertGreaterThan($previous->getTimestamp(), $entity->getUpdatedAt()?->getTimestamp() ?? 0);
    }

    public function testArchiveAndDeleteMarkers(): void
    {
        $entity = new TestEntity();
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $this->assertFalse($entity->isArchived());
        $this->assertNull($entity->getArchivedAt());
        $entity->archive();
        $this->assertTrue($entity->isArchived());
        $this->assertInstanceOf(DateTimeImmutable::class, $entity->getArchivedAt());
        $entity->unarchive();
        $this->assertFalse($entity->isArchived());
        $this->assertNull($entity->getArchivedAt());

        $this->assertNull($entity->getDeletedAt());
        $entity->markDeleted();
        $this->assertInstanceOf(DateTimeImmutable::class, $entity->getDeletedAt());
        $entity->restoreDeleted();
        $this->assertNull($entity->getDeletedAt());
    }

    public function testIdStableAcrossUpdates(): void
    {
        $entity = new TestEntity();
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $id = $entity->getId();

        $entity->archive();
        $this->entityManager->flush();

        $this->assertSame($id, $entity->getId());
    }

    public function testArchiveUsesProvidedTimestamp(): void
    {
        $entity = new TestEntity();
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $custom = new DateTimeImmutable('2024-08-01T00:00:00+00:00');
        $entity->archive($custom);

        $this->assertSame($custom, $entity->getArchivedAt());
    }

    public function testCreatedAtPreservedWhenProvided(): void
    {
        $entity = new TestEntity();

        $custom = new DateTimeImmutable('2024-01-01T00:00:00+00:00');
        $reflection = new \ReflectionProperty($entity, 'createdAt');
        $reflection->setAccessible(true);
        $reflection->setValue($entity, $custom);

        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $this->assertSame($custom, $entity->getCreatedAt());
    }

    protected function setUp(): void
    {
        $config = ORMSetup::createAttributeMetadataConfiguration(
            [__DIR__ . '/Fixtures/Entity'],
            true
        );

        $this->entityManager = EntityManager::create(
            ['driver' => 'pdo_sqlite', 'memory' => true],
            $config
        );

        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->createSchema([
            $this->entityManager->getClassMetadata(TestEntity::class),
        ]);
    }
}
