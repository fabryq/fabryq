<?php

/**
 * Registry entry representing a capability provider.
 *
 * @package   Fabryq\Runtime\Registry
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Runtime\Registry;

/**
 * Immutable definition of a capability provider implementation.
 */
final readonly class CapabilityProviderDefinition
{
    /**
     * @param string $capability Capability identifier.
     * @param string $contract   Contract class or interface name.
     * @param int    $priority   Provider priority.
     * @param string $serviceId  Service identifier in the container.
     * @param string $className  Class name implementing the provider.
     */
    public function __construct(
        /**
         * Capability identifier exposed by the provider.
         *
         * @var string
         */
        public string $capability,
        /**
         * Fully qualified contract class or interface.
         *
         * @var string
         */
        public string $contract,
        /**
         * Provider priority used for winner selection.
         *
         * @var int
         */
        public int $priority,
        /**
         * Service ID registered in the container.
         *
         * @var string
         */
        public string $serviceId,
        /**
         * Class name implementing the capability provider.
         *
         * @var string
         */
        public string $className,
    ) {
    }
}
