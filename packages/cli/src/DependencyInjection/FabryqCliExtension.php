<?php

/**
 * Symfony DI extension for the Fabryq CLI bundle.
 *
 * @package Fabryq\Cli\DependencyInjection
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

/**
 * Loads CLI service definitions into the container.
 */
final class FabryqCliExtension extends Extension
{
    /**
     * {@inheritDoc}
     *
     * Loads CLI services configuration.
     *
     * @param array<int, array<string, mixed>> $configs Bundle configuration arrays.
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.php');
    }
}
