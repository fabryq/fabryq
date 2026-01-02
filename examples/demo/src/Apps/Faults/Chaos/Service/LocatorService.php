<?php

declare(strict_types=1);

namespace App\Faults\Chaos\Service;

use Psr\Container\ContainerInterface;

final class LocatorService
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function fetch(string $id): object
    {
        return $this->container->get($id);
    }
}
