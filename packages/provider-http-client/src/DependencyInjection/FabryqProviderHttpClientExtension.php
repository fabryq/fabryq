<?php

/**
 * Symfony DI extension for the HTTP client provider bundle.
 *
 * @package   Fabryq\Provider\HttpClient\DependencyInjection
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Provider\HttpClient\DependencyInjection;

use Exception;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

/**
 * Registers HTTP client provider services in the container.
 */
final class FabryqProviderHttpClientExtension extends Extension
{
    /**
     * {@inheritDoc}
     *
     * Loads the provider services configuration from the bundle resources.
     *
     * @param array<int, array<string, mixed>> $configs [Optional] Bundle configuration arrays.
     *
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.php');
    }
}
