<?php

/**
 * Service definitions for the Fabryq runtime bundle.
 *
 * @package Fabryq\Runtime\Resources
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

use Fabryq\Runtime\Clock\ClockInterface;
use Fabryq\Runtime\Clock\SystemClock;
use Fabryq\Runtime\Context\FabryqContext;
use Fabryq\Runtime\Discovery\ComponentDiscovery;
use Fabryq\Runtime\Discovery\ManifestDiscovery;
use Fabryq\Runtime\Doctrine\DoctrineDiscovery;
use Fabryq\Runtime\Registry\AppRegistry;
use Fabryq\Runtime\Registry\AppRegistryFactory;
use Fabryq\Runtime\Registry\CapabilityProviderRegistry;
use Fabryq\Runtime\Registry\CapabilityProviderRegistryFactory;
use Fabryq\Runtime\Resources\ResourceRegistry;
use Fabryq\Runtime\Routing\FabryqRouteLoader;
use Fabryq\Runtime\Ulid\UlidFactory;
use Fabryq\Runtime\Ulid\UlidFactoryInterface;
use Fabryq\Runtime\Util\ComponentSlugger;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

/**
 * Register runtime services, registries, and route loaders.
 *
 * @param ContainerConfigurator $configurator Symfony DI configurator.
 *
 * @return void
 */
return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services->set(ComponentSlugger::class);
    $services->set(SystemClock::class);
    $services->alias(ClockInterface::class, SystemClock::class);
    $services->set(UlidFactory::class);
    $services->alias(UlidFactoryInterface::class, UlidFactory::class);
    $services->set(FabryqContext::class);
    $services->set(ManifestDiscovery::class);
    $services->set(ComponentDiscovery::class);
    $services->set(AppRegistryFactory::class);

    $services->set(AppRegistry::class)
        ->factory([service(AppRegistryFactory::class), 'build'])
        ->args(['%kernel.project_dir%']);

    $services->set(ResourceRegistry::class);
    $services->set(DoctrineDiscovery::class)
        ->args([service(AppRegistry::class), '%kernel.project_dir%']);

    $services->set(FabryqRouteLoader::class)
        ->arg('$attributeDirectoryLoader', service('routing.loader.attribute.directory'))
        ->tag('routing.loader');

    $services->set(CapabilityProviderRegistryFactory::class);
    $services->set(CapabilityProviderRegistry::class)
        ->factory([service(CapabilityProviderRegistryFactory::class), 'create'])
        ->args(['%fabryq.capability_providers%', '%fabryq.capability_provider_issues%']);
};
