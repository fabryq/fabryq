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
     * Return the entity identifier.
     *
     * @return string Entity identifier (ULID string).
     */
    public function getId(): string;

    /**
     * Get the creation timestamp.
     *
     * @return DateTimeImmutable Creation timestamp.
     */
    public function getCreatedAt(): DateTimeImmutable;

    /**
     * Get the last updated timestamp.
     *
     * @return DateTimeImmutable|null Last updated timestamp or null if never updated.
     */
    public function getUpdatedAt(): ?DateTimeImmutable;

    /**
     * Get the deletion timestamp.
     *
     * @return DateTimeImmutable|null Deletion timestamp or null if not deleted.
     */
    public function getDeletedAt(): ?DateTimeImmutable;

    /**
     * Get the archived timestamp.
     *
     * @return DateTimeImmutable|null Archived timestamp or null if not archived.
     */
    public function getArchivedAt(): ?DateTimeImmutable;

    /**
     * Mark the entity as archived.
     *
     * @param DateTimeImmutable|null $at Archive timestamp or null to use current time.
     */
    public function archive(?DateTimeImmutable $at = null): void;

    /**
     * Remove the archived marker.
     */
    public function unarchive(): void;

    /**
     * Check if the entity is archived.
     *
     * @return bool True when archivedAt is set.
     */
    public function isArchived(): bool;

    /**
     * Mark the entity as deleted.
     *
     * @param DateTimeImmutable|null $at Deletion timestamp or null to use current time.
     */
    public function markDeleted(?DateTimeImmutable $at = null): void;

    /**
     * Clear the deletion marker.
     */
    public function restoreDeleted(): void;
}
