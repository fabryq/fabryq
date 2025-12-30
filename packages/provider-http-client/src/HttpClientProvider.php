<?php

/**
 * Capability provider for HTTP client access.
 *
 * @package Fabryq\Provider\HttpClient
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Provider\HttpClient;

use Fabryq\Contracts\Capability\CapabilityIds;
use Fabryq\Contracts\Capability\CapabilityProviderInterface;
use Fabryq\Contracts\Capability\HttpClientInterface;

/**
 * Exposes an HTTP client capability to the registry.
 */
final class HttpClientProvider implements CapabilityProviderInterface
{
    /**
     * @param HttpClientInterface $client Concrete HTTP client implementation.
     */
    public function __construct(
        /**
         * Concrete HTTP client instance used by this provider.
         *
         * @var HttpClientInterface
         */
        private readonly HttpClientInterface $client
    )
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getCapabilityId(): string
    {
        return CapabilityIds::FABRYQ_CLIENT_HTTP;
    }

    /**
     * {@inheritDoc}
     */
    public function getContract(): string
    {
        return HttpClientInterface::class;
    }

    /**
     * Return the underlying HTTP client instance.
     *
     * @return HttpClientInterface Configured HTTP client implementation.
     */
    public function getClient(): HttpClientInterface
    {
        return $this->client;
    }
}
