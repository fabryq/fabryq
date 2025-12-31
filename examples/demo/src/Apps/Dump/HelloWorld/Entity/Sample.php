<?php

/**
 * Sample entity used by the HelloWorld demo component.
 *
 * @package   App\Test\HelloWorld\Entity
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace App\Dump\HelloWorld\Entity;

use Doctrine\ORM\Mapping as ORM;
use Fabryq\Runtime\Entity\AbstractFabryqEntity;

/**
 * Simple entity representing a demo record.
 */
#[ORM\Entity]
#[ORM\Table(name: 'app_dump__hello-world__sample')]
class Sample extends AbstractFabryqEntity
{
 
}
