<?php

/**
 * Test entity for lifecycle callbacks.
 *
 * @package   Fabryq\Tests\Fixtures\Entity
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;
use Fabryq\Runtime\Entity\AbstractFabryqEntity;

/**
 * Sample entity for lifecycle tests.
 */
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
final class TestEntity extends AbstractFabryqEntity
{
}
