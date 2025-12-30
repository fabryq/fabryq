<?php

/**
 * Registry container for capability providers and validation issues.
 *
 * @package Fabryq\Runtime\Registry
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Runtime\Registry;

/**
 * Holds capability provider definitions and related validation issues.
 */
final class CapabilityProviderRegistry
{
    /**
     * @param CapabilityProviderDefinition[] $providers Provider definitions.
     * @param ValidationIssue[] $issues Validation issues collected during discovery.
     */
    public function __construct(
        /**
         * Provider definitions by registration order.
         *
         * @var CapabilityProviderDefinition[]
         */
        private readonly array $providers,
        /**
         * Validation issues encountered while collecting providers.
         *
         * @var ValidationIssue[]
         */
        private readonly array $issues,
    ) {
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
     * Find a provider by capability identifier.
     *
     * @param string $capabilityId Capability identifier to look up.
     *
     * @return CapabilityProviderDefinition|null Matching provider or null when none exists.
     */
    public function findByCapabilityId(string $capabilityId): ?CapabilityProviderDefinition
    {
        foreach ($this->providers as $provider) {
            if ($provider->capabilityId === $capabilityId) {
                return $provider;
            }
        }

        return null;
    }
}
