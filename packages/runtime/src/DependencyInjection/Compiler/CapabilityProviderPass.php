<?php

/**
 * Compiler pass that collects capability providers and validation issues.
 *
 * @package Fabryq\Runtime\DependencyInjection\Compiler
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Runtime\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Collects tagged capability providers into container parameters and resolves winners.
 *
 * Side effects:
 * - Adds or replaces container parameters for providers, issues, and maps.
 */
final class CapabilityProviderPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     *
     * @param ContainerBuilder $container Container being compiled.
     */
    public function process(ContainerBuilder $container): void
    {
        $tagged = $container->findTaggedServiceIds('fabryq.capability_provider');
        $providers = [];
        $issues = [];
        $providersByContract = [];

        foreach ($tagged as $serviceId => $tags) {
            $definition = $container->getDefinition($serviceId);
            $className = $definition->getClass() ?? $serviceId;

            foreach ($tags as $tag) {
                $capability = $tag['capability'] ?? null;
                $contract = $tag['contract'] ?? null;
                $priority = (int) ($tag['priority'] ?? 0);

                if (!$capability || !$contract) {
                    $issues[] = [
                        'ruleKey' => 'FABRYQ.PROVIDER.INVALID',
                        'message' => sprintf('Provider %s is missing capability or contract.', $serviceId),
                        'file' => null,
                        'line' => null,
                        'symbol' => null,
                    ];
                    continue;
                }

                if (!$this->isValidCapability($capability)) {
                    $issues[] = [
                        'ruleKey' => 'FABRYQ.PROVIDER.INVALID',
                        'message' => sprintf('Capability "%s" does not match schema "fabryq.bridge.*".', $capability),
                        'file' => null,
                        'line' => null,
                        'symbol' => null,
                    ];
                    continue;
                }

                if (!interface_exists($contract)) {
                    $issues[] = [
                        'ruleKey' => 'FABRYQ.PROVIDER.INVALID',
                        'message' => sprintf('Contract "%s" must be an interface.', $contract),
                        'file' => null,
                        'line' => null,
                        'symbol' => null,
                    ];
                    continue;
                }

                if (!class_exists($className) || !is_subclass_of($className, $contract)) {
                    $issues[] = [
                        'ruleKey' => 'FABRYQ.PROVIDER.INVALID',
                        'message' => sprintf('Provider %s must implement %s.', $className, $contract),
                        'file' => null,
                        'line' => null,
                        'symbol' => null,
                    ];
                    continue;
                }

                $providers[] = [
                    'capability' => (string) $capability,
                    'contract' => (string) $contract,
                    'priority' => $priority,
                    'serviceId' => (string) $serviceId,
                    'className' => (string) $className,
                ];

                $providersByContract[$contract][] = [
                    'capability' => (string) $capability,
                    'contract' => (string) $contract,
                    'priority' => $priority,
                    'serviceId' => (string) $serviceId,
                    'className' => (string) $className,
                ];
            }
        }

        $container->setParameter('fabryq.capability_providers', $providers);
        $container->setParameter('fabryq.capability_provider_issues', $issues);

        $capabilityMap = $this->buildCapabilityMap($providersByContract);
        $container->setParameter('fabryq.capabilities.map', $capabilityMap);

        foreach ($capabilityMap as $entry) {
            if (!isset($entry['winner']['serviceId'], $entry['contract'])) {
                continue;
            }

            $alias = $container->setAlias($entry['contract'], $entry['winner']['serviceId']);
            $alias->setPublic(true);
        }
    }

    /**
     * Build a capability diagnostic map with winners per contract.
     *
     * @param array<string, array<int, array<string, mixed>>> $providersByContract
     *
     * @return array<string, array<string, mixed>>
     */
    private function buildCapabilityMap(array $providersByContract): array
    {
        $map = [];

        foreach ($providersByContract as $contract => $providers) {
            usort($providers, static function (array $a, array $b): int {
                if ($a['priority'] === $b['priority']) {
                    return $a['serviceId'] <=> $b['serviceId'];
                }

                return $b['priority'] <=> $a['priority'];
            });

            $winner = $providers[0] ?? null;

            foreach ($providers as $provider) {
                $capability = $provider['capability'];
                $map[$capability] = [
                    'capability' => $capability,
                    'contract' => $contract,
                    'providers' => array_map(
                        static function (array $entry) use ($winner): array {
                            return [
                                'serviceId' => $entry['serviceId'],
                                'className' => $entry['className'],
                                'priority' => $entry['priority'],
                                'capability' => $entry['capability'],
                                'winner' => $winner !== null && $entry['serviceId'] === $winner['serviceId'],
                            ];
                        },
                        $providers
                    ),
                    'winner' => $winner === null ? null : [
                        'serviceId' => $winner['serviceId'],
                        'className' => $winner['className'],
                        'priority' => $winner['priority'],
                        'capability' => $winner['capability'],
                    ],
                ];
            }
        }

        return $map;
    }

    /**
     * Validate capability id format.
     *
     * @param string $capability Capability identifier.
     *
     * @return bool True when valid.
     */
    private function isValidCapability(string $capability): bool
    {
        return (bool) preg_match('/^fabryq\\.bridge\\.[a-z0-9]+(?:-[a-z0-9]+)*(?:\\.[a-z0-9]+(?:-[a-z0-9]+)*)+$/', $capability);
    }
}
