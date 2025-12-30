<?php

/**
 * Sample entity used by the HelloWorld demo component.
 *
 * @package App\Test\HelloWorld\Entity
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace App\Test\HelloWorld\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Simple entity representing a demo record.
 */
#[ORM\Entity]
#[ORM\Table(name: 'app_test__hello-world__sample')]
class Sample
{
    /**
     * Primary identifier for the entity.
     *
     * @var string
     */
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 26)]
    private string $id;

    /**
     * @param string $id Entity identifier.
     */
    public function __construct(string $id)
    {
        $this->id = $id;
    }

    /**
     * Return the entity identifier.
     *
     * @return string Entity identifier.
     */
    public function getId(): string
    {
        return $this->id;
    }
}
