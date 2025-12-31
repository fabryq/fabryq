<?php

/**
 * Application kernel for the skeleton project.
 *
 * @package App
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

/**
 * Configures services and routes for the skeleton application.
 */
final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    /**
     * {@inheritDoc}
     */
    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->import('../config/packages/*.yaml');
        $container->import('../config/packages/'.$this->environment.'/*.yaml');
        $container->import('../config/services.yaml');
    }

    /**
     * {@inheritDoc}
     */
    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('../config/routes/*.yaml');
        $routes->import('../config/routes/'.$this->environment.'/*.yaml');
    }
}
