<?php

/**
 * Simple HTTP client implementation used for demos and testing.
 *
 * @package Fabryq\Provider\HttpClient
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Provider\HttpClient;

use Fabryq\Contracts\Capability\HttpClientInterface;

/**
 * Minimal HTTP client that formats requests instead of sending them.
 *
 * Responsibilities:
 * - Provide a deterministic string output for request inputs.
 */
final class SimpleHttpClient implements HttpClientInterface
{
    /**
     * {@inheritDoc}
     *
     * This implementation does not perform network I/O and returns a formatted string.
     */
    public function request(string $method, string $url, array $options = []): string
    {
        return sprintf('%s %s', strtoupper($method), $url);
    }
}
