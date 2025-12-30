<?php

/**
 * Factory for building capability provider registries from raw arrays.
 *
 * @package Fabryq\Runtime\Registry
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Runtime\Registry;

/**
 * Normalizes provider and issue payloads into registry objects.
 */
final class CapabilityProviderRegistryFactory
{
    /**
     * Build a capability provider registry from raw provider and issue data.
     *
     * @param array<int, array<string, string>> $providers Provider payloads containing capability metadata.
     * @param array<int, array<string, mixed>> $issues Issue payloads collected during discovery.
     *
     * @return CapabilityProviderRegistry Normalized registry with providers and validation issues.
     */
    public function create(array $providers, array $issues): CapabilityProviderRegistry
    {
        $definitions = [];
        foreach ($providers as $provider) {
            $definitions[] = new CapabilityProviderDefinition(
                $provider['capabilityId'],
                $provider['contract'],
                $provider['serviceId'],
                $provider['className']
            );
        }

        $validationIssues = [];
        foreach ($issues as $issue) {
            $validationIssues[] = new ValidationIssue(
                $issue['ruleKey'],
                $issue['message'],
                $issue['file'] ?? null,
                $issue['line'] ?? null,
                $issue['symbol'] ?? null,
            );
        }

        return new CapabilityProviderRegistry($definitions, $validationIssues);
    }
}
