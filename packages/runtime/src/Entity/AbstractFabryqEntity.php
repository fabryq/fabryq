<?php

/**
 * Base entity abstraction for Fabryq runtime.
 *
 * @package   Fabryq\Runtime\Entity
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Runtime\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Ulid;

/**
 * Provides a minimal ID accessor for entities.
 */
abstract class AbstractFabryqEntity implements FabryqEntityInterface
{
    /**
     * @var Ulid
     */
    protected Ulid $id;

    /**
     * @var DateTimeImmutable|null
     */
    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE, nullable: false, options: ["default" => "CURRENT_TIMESTAMP"])]
    #[Groups(["all", "default", "status"])]
    protected ?DateTimeImmutable $createdAt = null;

    /**
     * @var DateTimeImmutable|null
     */
    #[ORM\Column(name: 'deleted_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?DateTimeImmutable $deletedAt = null;

    /**
     * @var DateTimeImmutable|null
     */
    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE, nullable: false, options: ["default" => "CURRENT_TIMESTAMP"])]
    #[Groups(["all", "default", "status"])]
    protected ?DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
        $this->deletedAt = null;
        $this->id = new Ulid();
    }

    /**
     * {@inheritDoc}
     */
    final public function getId(): string
    {
        return $this->id->toRfc4122();
    }

    /**
     * {@inheritDoc}
     */
    final public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * {@inheritDoc}
     */
    final public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt ?? $this->createdAt;
    }

    /**
     * {@inheritDoc}
     */
    final public function setUpdatedAt(DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    final public function getDeletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    /**
     * {@inheritDoc}
     */
    final public function setDeletedAt(?DateTimeImmutable $deletedAt): self
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }
}
