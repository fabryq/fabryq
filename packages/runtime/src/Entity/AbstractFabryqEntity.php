<?php

/**
 * Base entity abstraction for Fabryq runtime.
 *
 * @package   Fabryq\Runtime\Entity
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Runtime\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Ulid;

/**
 * Provides standard entity fields and lifecycle callbacks.
 */
#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractFabryqEntity implements FabryqEntityInterface
{
    use FabryqEntityTrait;

    public function __construct()
    {
        $this->id = (new Ulid())->toBase32();
    }
}
