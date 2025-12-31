<?php

/**
 * Registry container for capability providers and validation issues.
 *
 * @package   Fabryq\Runtime\Registry
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Runtime\Registry;

/**
 * Holds capability provider definitions and related validation issues.
 */
final readonly class CapabilityProviderRegistry
{
    /**
     * @param CapabilityProviderDefinition[] $providers Provider definitions.
     * @param ValidationIssue[]              $issues    Validation issues collected during discovery.
     */
    public function __construct(
        /**
         * Provider definitions by registration order.
         *
         * @var CapabilityProviderDefinition[]
         */
        private array $providers,
        /**
         * Validation issues encountered while collecting providers.
         *
         * @var ValidationIssue[]
         */
        private array $issues,
    ) {}

    /**
     * Find a provider by capability identifier.
     *
     * @param string $capability Capability identifier to look up.
     *
     * @return CapabilityProviderDefinition|null Matching provider or null when none exists.
     */
    public function findByCapability(string $capability): ?CapabilityProviderDefinition
    {
        foreach ($this->providers as $provider) {
            if ($provider->capability === $capability) {
                return $provider;
            }
        }

        return null;
    }

    /**
     * Backwards-compatible lookup by capability identifier.
     *
     * @param string $capabilityId Capability identifier to look up.
     *
     * @return CapabilityProviderDefinition|null Matching provider or null when none exists.
     */
    public function findByCapabilityId(string $capabilityId): ?CapabilityProviderDefinition
    {
        return $this->findByCapability($capabilityId);
    }

    /**
     * Return validation issues discovered during provider registration.
     *
     * @return ValidationIssue[]
     */
    public function getIssues(): array
    {
        return $this->issues;
    }

    /**
     * Return all registered capability provider definitions.
     *
     * @return CapabilityProviderDefinition[]
     */
    public function getProviders(): array
    {
        return $this->providers;
    }
}
