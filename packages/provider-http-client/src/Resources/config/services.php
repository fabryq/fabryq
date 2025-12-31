<?php

/**
 * Service definitions for the HTTP client provider bundle.
 *
 * @package Fabryq\Provider\HttpClient\Resources
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

use Fabryq\Provider\HttpClient\SimpleHttpClient;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

/**
 * Register HTTP client services and capability provider attributes.
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

    $services->set(SimpleHttpClient::class);

    $services->alias(Fabryq\Contracts\Capability\HttpClientInterface::class, SimpleHttpClient::class);
};
