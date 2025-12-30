<?php

/**
 * Registry entry representing a capability provider.
 *
 * @package Fabryq\Runtime\Registry
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Runtime\Registry;

/**
 * Immutable definition of a capability provider implementation.
 */
final class CapabilityProviderDefinition
{
    /**
     * @param string $capabilityId Capability identifier.
     * @param string $contract Contract class or interface name.
     * @param string $serviceId Service identifier in the container.
     * @param string $className Class name implementing the provider.
     */
    public function __construct(
        /**
         * Capability identifier exposed by the provider.
         *
         * @var string
         */
        public readonly string $capabilityId,
        /**
         * Fully qualified contract class or interface.
         *
         * @var string
         */
        public readonly string $contract,
        /**
         * Service ID registered in the container.
         *
         * @var string
         */
        public readonly string $serviceId,
        /**
         * Class name implementing the capability provider.
         *
         * @var string
         */
        public readonly string $className,
    ) {
    }
}
