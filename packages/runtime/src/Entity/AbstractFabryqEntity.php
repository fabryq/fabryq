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

/**
 * Provides a minimal ID accessor for entities.
 */
abstract class AbstractFabryqEntity implements FabryqEntityInterface
{
    /**
     * @var string
     */
    protected string $id;

    /**
     * @var DateTimeImmutable|null
     */
    protected ?DateTimeImmutable $createdAt = null;

    /**
     * @var DateTimeImmutable|null
     */
    protected ?DateTimeImmutable $deletedAt = null;

    /**
     * @var DateTimeImmutable|null
     */
    protected ?DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    /**
     * {@inheritDoc}
     */
    final public function getId(): string
    {
        return $this->id;
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
