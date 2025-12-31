<?php

/**
 * Shared entity lifecycle fields and helpers.
 *
 * @package   Fabryq\Runtime\Entity
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Runtime\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Ulid;

/**
 * Reusable entity fields and lifecycle callbacks.
 */
trait FabryqEntityTrait
{
    /**
     * @var string
     */
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[ORM\Column(name: 'id', type: 'string', length: 26, unique: true, nullable: false)]
    protected string $id;

    /**
     * @var DateTimeImmutable|null
     */
    #[ORM\Column(name: 'archived_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    protected ?DateTimeImmutable $archivedAt = null;

    /**
     * @var DateTimeImmutable
     */
    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE, nullable: false)]
    protected DateTimeImmutable $createdAt;

    /**
     * @var DateTimeImmutable|null
     */
    #[ORM\Column(name: 'deleted_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    protected ?DateTimeImmutable $deletedAt = null;

    /**
     * @var DateTimeImmutable|null
     */
    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    protected ?DateTimeImmutable $updatedAt = null;

    /**
     * {@inheritDoc}
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * {@inheritDoc}
     */
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * {@inheritDoc}
     */
    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * {@inheritDoc}
     */
    public function getDeletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    /**
     * {@inheritDoc}
     */
    public function getArchivedAt(): ?DateTimeImmutable
    {
        return $this->archivedAt;
    }

    /**
     * {@inheritDoc}
     */
    public function archive(?DateTimeImmutable $at = null): void
    {
        $this->archivedAt = $at ?? new DateTimeImmutable();
    }

    /**
     * {@inheritDoc}
     */
    public function unarchive(): void
    {
        $this->archivedAt = null;
    }

    /**
     * {@inheritDoc}
     */
    public function isArchived(): bool
    {
        return $this->archivedAt !== null;
    }

    /**
     * {@inheritDoc}
     */
    public function markDeleted(?DateTimeImmutable $at = null): void
    {
        $this->deletedAt = $at ?? new DateTimeImmutable();
    }

    /**
     * {@inheritDoc}
     */
    public function restoreDeleted(): void
    {
        $this->deletedAt = null;
    }

    /**
     * Set defaults before persisting.
     */
    #[ORM\PrePersist]
    public function fabryqOnPrePersist(): void
    {
        if (!isset($this->id) || $this->id === '') {
            $this->id = (new Ulid())->toBase32();
        }

        $now = new DateTimeImmutable();
        if (!isset($this->createdAt)) {
            $this->createdAt = $now;
        }
        if ($this->updatedAt === null) {
            $this->updatedAt = $now;
        }
    }

    /**
     * Update timestamps before update.
     */
    #[ORM\PreUpdate]
    public function fabryqOnPreUpdate(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
