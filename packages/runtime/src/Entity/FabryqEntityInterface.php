<?php

/**
 * Entity interface for Fabryq runtime.
 *
 * @package   Fabryq\Runtime\Entity
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Runtime\Entity;

use DateTimeImmutable;

/**
 * Defines the minimum contract for domain entities.
 */
interface FabryqEntityInterface
{
    /**
     * Get the creation timestamp.
     *
     * @return DateTimeImmutable Creation timestamp.
     */
    public function getCreatedAt(): DateTimeImmutable;

    /**
     * Get the deletion timestamp.
     *
     * @return DateTimeImmutable|null Deletion timestamp or null if not deleted.
     */
    public function getDeletedAt(): ?DateTimeImmutable;

    /**
     * Return the entity identifier.
     *
     * @return string Entity identifier.
     */
    public function getId(): string;

    /**
     * Get the last updated timestamp.
     *
     * @return DateTimeImmutable Last updated timestamp.
     */
    public function getUpdatedAt(): DateTimeImmutable;

    /**
     * Set the deletion timestamp.
     *
     * @param DateTimeImmutable|null $deletedAt Deletion timestamp or null if not deleted.
     */
    public function setDeletedAt(?DateTimeImmutable $deletedAt): self;

    /**
     * Set the last updated timestamp.
     *
     * @param DateTimeImmutable $updatedAt Last updated timestamp.
     */
    public function setUpdatedAt(DateTimeImmutable $updatedAt): self;
}
