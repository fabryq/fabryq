<?php

declare(strict_types=1);

namespace App\Faults\Chaos\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
final class BadEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 26)]
    private string $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }
}
