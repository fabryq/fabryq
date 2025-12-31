<?php

/**
 * Base entity abstraction for Fabryq runtime.
 *
 * @package   Fabryq\Runtime\Entity
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Runtime\Entity;

use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Ulid;

/**
 * Provides a minimal ID accessor for entities.
 */
abstract class AbstractFabryqEntity implements FabryqEntityInterface
{
    /**
     * @var Ulid
     */
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[ORM\Column(name: "id", type: "ulid", unique: true, nullable: false)]
    protected Ulid $id;

    /**
     * @var DateTime|null
     */
    #[ORM\Column(name: 'archived_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?DateTime $archivedAt = null;

    /**
     * @var DateTime|null
     */
    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE, nullable: false, options: ["default" => "CURRENT_TIMESTAMP"])]
    protected ?DateTime $createdAt = null;

    /**
     * @var DateTime|null
     */
    #[ORM\Column(name: 'deleted_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?DateTime $deletedAt = null;

    /**
     * @var DateTime|null
     */
    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE, nullable: false, options: ["default" => "CURRENT_TIMESTAMP"])]
    protected ?DateTime $updatedAt = null;

    public function __construct()
    {
        $this->updatedAt = new DateTime();
        $this->id = new Ulid();
    }

    /**
     * {@inheritDoc}
     */
    final public function getId(): string
    {
        return $this->id->toBase32();
    }

    /**
     * {@inheritDoc}
     */
    final public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * {@inheritDoc}
     */
    final public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt ?? $this->createdAt;
    }

    #[ORM\PreUpdate]
    final protected function setUpdatedAt(): self
    {
        $this->updatedAt = new DateTime();
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    final public function getDeletedAt(): ?DateTime
    {
        return $this->deletedAt;
    }

    /**
     * {@inheritDoc}
     */
    final public function setDeletedAt(?DateTime $deletedAt): self
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    final public function getArchivedAt(): ?DateTime
    {
        return $this->archivedAt;
    }

    /**
     * {@inheritDoc}
     */
    final public function setArchivedAt(?DateTime $archivedAt): self
    {
        $this->archivedAt = $archivedAt;
        return $this;
    }
}
