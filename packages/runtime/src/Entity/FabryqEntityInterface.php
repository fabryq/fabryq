<?php

/**
 * Entity interface for Fabryq runtime.
 *
 * @package   Fabryq\Runtime\Entity
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Runtime\Entity;

use DateTime;

/**
 * Defines the minimum contract for domain entities.
 */
interface FabryqEntityInterface
{
    /**
     * Get the archived timestamp.
     *
     * @return DateTime|null Archived timestamp or null if not archived.
     */
    public function getArchivedAt(): ?DateTime;

    /**
     * Get the creation timestamp.
     *
     * @return DateTime Creation timestamp.
     */
    public function getCreatedAt(): DateTime;

    /**
     * Get the deletion timestamp.
     *
     * @return DateTime|null Deletion timestamp or null if not deleted.
     */
    public function getDeletedAt(): ?DateTime;

    /**
     * Return the entity identifier.
     *
     * @return string Entity identifier.
     */
    public function getId(): string;

    /**
     * Get the last updated timestamp.
     *
     * @return DateTime Last updated timestamp.
     */
    public function getUpdatedAt(): DateTime;

    /**
     * Set the archived timestamp.
     *
     * @param DateTime|null $archivedAt Archived timestamp or null if not archived.
     */
    public function setArchivedAt(?DateTime $archivedAt): self;

    /**
     * Set the deletion timestamp.
     *
     * @param DateTime|null $deletedAt Deletion timestamp or null if not deleted.
     */
    public function setDeletedAt(?DateTime $deletedAt): self;
}
