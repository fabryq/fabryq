<?php

/**
 * HTTP client contract for capability providers.
 *
 * @package Fabryq\Contracts\Capability
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Contracts\Capability;

/**
 * Minimal HTTP client abstraction used by capability providers.
 *
 * Responsibilities:
 * - Execute outbound HTTP requests for consumers.
 */
interface HttpClientInterface
{
    /**
     * Execute an HTTP request and return the response body.
     *
     * @param string $method HTTP method (e.g. GET, POST).
     * @param string $url Absolute or relative request URL.
     * @param array<string, mixed> $options [Optional] Transport-specific options such as headers or timeouts.
     *
     * @return string Response body as a string.
     */
    public function request(string $method, string $url, array $options = []): string;
}
