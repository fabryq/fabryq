<?php

/**
 * Capability identifier constants for Fabryq capabilities.
 *
 * @package   Fabryq\Contracts\Capability
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Contracts\Capability;

/**
 * Enumerates stable capability identifiers used across the registry.
 *
 * Invariants:
 * - Identifiers are globally unique within a registry scope.
 * - Values remain stable across releases to preserve compatibility.
 */
final class CapabilityIds
{
    /**
     * Capability ID for the HTTP client capability.
     *
     * Stable identifier used by providers and consumers.
     */
    public const string FABRYQ_CLIENT_HTTP = 'fabryq.bridge.core.http-client';
}
