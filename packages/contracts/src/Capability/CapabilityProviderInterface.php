<?php

/**
 * Contracts for capability provider implementations.
 *
 * @package Fabryq\Contracts\Capability
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Contracts\Capability;

/**
 * Describes a provider that supplies a capability and its contract.
 *
 * Invariants:
 * - Capability IDs are stable and unique within a registry.
 * - Contract identifiers resolve to a compatible interface or class name.
 */
interface CapabilityProviderInterface
{
    /**
     * Return the stable capability identifier.
     *
     * @return string Capability ID used for registry lookups.
     */
    public function getCapabilityId(): string;

    /**
     * Return the contract name provided by this capability.
     *
     * @return string Fully qualified class or interface name of the contract.
     */
    public function getContract(): string;
}
