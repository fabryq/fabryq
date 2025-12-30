<?php

/**
 * Service definitions for the HTTP client provider bundle.
 *
 * @package Fabryq\Provider\HttpClient\Resources
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

use Fabryq\Contracts\Capability\CapabilityIds;
use Fabryq\Contracts\Capability\HttpClientInterface;
use Fabryq\Provider\HttpClient\HttpClientProvider;
use Fabryq\Provider\HttpClient\SimpleHttpClient;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

/**
 * Register HTTP client services and capability provider tags.
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
    $services->set(HttpClientInterface::class)
        ->alias(SimpleHttpClient::class);

    $services->set(HttpClientProvider::class)
        ->tag('fabryq.capability_provider', [
            'capabilityId' => CapabilityIds::FABRYQ_CLIENT_HTTP,
            'contract' => HttpClientInterface::class,
        ]);
};
