<?php

/**
 * Service definitions for the Fabryq runtime bundle.
 *
 * @package Fabryq\Runtime\Resources
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

use Fabryq\Runtime\Discovery\ComponentDiscovery;
use Fabryq\Runtime\Discovery\ManifestDiscovery;
use Fabryq\Runtime\Doctrine\DoctrineDiscovery;
use Fabryq\Runtime\Registry\AppRegistry;
use Fabryq\Runtime\Registry\AppRegistryFactory;
use Fabryq\Runtime\Registry\CapabilityProviderRegistry;
use Fabryq\Runtime\Registry\CapabilityProviderRegistryFactory;
use Fabryq\Runtime\Resources\ResourceRegistry;
use Fabryq\Runtime\Routing\FabryqRouteLoader;
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
    $services->set(ManifestDiscovery::class);
    $services->set(ComponentDiscovery::class);
    $services->set(AppRegistryFactory::class);

    $services->set(AppRegistry::class)
        ->factory([service(AppRegistryFactory::class), 'build'])
        ->args(['%kernel.project_dir%']);

    $services->set(ResourceRegistry::class);
    $services->set(DoctrineDiscovery::class);

    $services->set(FabryqRouteLoader::class)
        ->tag('routing.loader');

    $services->set(CapabilityProviderRegistryFactory::class);
    $services->set(CapabilityProviderRegistry::class)
        ->factory([service(CapabilityProviderRegistryFactory::class), 'create'])
        ->args(['%fabryq.capability_providers%', '%fabryq.capability_provider_issues%']);
};
