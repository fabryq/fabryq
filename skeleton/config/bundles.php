<?php

declare(strict_types=1);

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class => ['all' => true],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
    Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle::class => ['all' => true],
    Fabryq\Runtime\FabryqRuntimeBundle::class => ['all' => true],
    Fabryq\Cli\FabryqCliBundle::class => ['all' => true],
    Fabryq\Provider\HttpClient\FabryqProviderHttpClientBundle::class => ['all' => true],
];
