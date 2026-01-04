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
 *
 * @phpstan-type ProviderPayload array{
 *   capability: string,
 *   contract: string,
 *   priority?: int,
 *   serviceId: string,
 *   className: string
 * }
 * @phpstan-type IssuePayload array{
 *   ruleKey: string,
 *   message: string,
 *   file?: string|null,
 *   line?: int|null,
 *   symbol?: string|null
 * }
 */
final class CapabilityProviderRegistryFactory
{
    /**
     * Build a capability provider registry from raw provider and issue data.
     *
     * @param list<array<string, mixed>> $providers Provider payloads containing capability metadata.
     * @param list<array<string, mixed>> $issues Issue payloads collected during discovery.
     *
     * @return CapabilityProviderRegistry Normalized registry with providers and validation issues.
     */
    public function create(array $providers, array $issues): CapabilityProviderRegistry
    {
        $definitions = [];
        foreach ($providers as $provider) {
            $provider = $this->normalizeProvider($provider);
            $definitions[] = new CapabilityProviderDefinition(
                $provider['capability'],
                $provider['contract'],
                $provider['priority'] ?? 0,
                $provider['serviceId'],
                $provider['className']
            );
        }

        $validationIssues = [];
        foreach ($issues as $issue) {
            $issue = $this->normalizeIssue($issue);
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

    /**
     * Normalize a provider payload.
     *
     * @param array<string, mixed> $provider Raw provider payload.
     *
     * @return ProviderPayload
     */
    private function normalizeProvider(array $provider): array
    {
        if (
            !isset($provider['capability'], $provider['contract'], $provider['serviceId'], $provider['className'])
            || !is_string($provider['capability'])
            || !is_string($provider['contract'])
            || !is_string($provider['serviceId'])
            || !is_string($provider['className'])
        ) {
            throw new \InvalidArgumentException('Invalid provider payload.');
        }

        $priority = $provider['priority'] ?? 0;
        if (!is_int($priority)) {
            throw new \InvalidArgumentException('Invalid provider priority.');
        }

        return [
            'capability' => $provider['capability'],
            'contract' => $provider['contract'],
            'priority' => $priority,
            'serviceId' => $provider['serviceId'],
            'className' => $provider['className'],
        ];
    }

    /**
     * Normalize an issue payload.
     *
     * @param array<string, mixed> $issue Raw issue payload.
     *
     * @return IssuePayload
     */
    private function normalizeIssue(array $issue): array
    {
        if (!isset($issue['ruleKey'], $issue['message']) || !is_string($issue['ruleKey']) || !is_string($issue['message'])) {
            throw new \InvalidArgumentException('Invalid issue payload.');
        }

        return [
            'ruleKey' => $issue['ruleKey'],
            'message' => $issue['message'],
            'file' => isset($issue['file']) && is_string($issue['file']) ? $issue['file'] : null,
            'line' => isset($issue['line']) && is_int($issue['line']) ? $issue['line'] : null,
            'symbol' => isset($issue['symbol']) && is_string($issue['symbol']) ? $issue['symbol'] : null,
        ];
    }
}
