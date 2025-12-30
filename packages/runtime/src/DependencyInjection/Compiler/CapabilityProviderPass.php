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
 * Collects tagged capability providers into container parameters.
 *
 * Side effects:
 * - Adds or replaces container parameters for providers and issues.
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
        $seen = [];

        foreach ($tagged as $serviceId => $tags) {
            $definition = $container->getDefinition($serviceId);
            $className = $definition->getClass() ?? $serviceId;

            foreach ($tags as $tag) {
                $capabilityId = $tag['capabilityId'] ?? null;
                $contract = $tag['contract'] ?? null;

                if (!$capabilityId || !$contract) {
                    $issues[] = [
                        'ruleKey' => 'FABRYQ.PROVIDER.INVALID',
                        'message' => sprintf('Provider %s is missing capabilityId or contract.', $serviceId),
                        'file' => null,
                        'line' => null,
                        'symbol' => null,
                    ];
                    continue;
                }

                if (isset($seen[$capabilityId])) {
                    $issues[] = [
                        'ruleKey' => 'FABRYQ.PROVIDER.DUPLICATE',
                        'message' => sprintf('Capability "%s" has multiple providers (%s, %s).', $capabilityId, $seen[$capabilityId], $serviceId),
                        'file' => null,
                        'line' => null,
                        'symbol' => null,
                    ];
                    continue;
                }

                $seen[$capabilityId] = $serviceId;
                $providers[] = [
                    'capabilityId' => (string) $capabilityId,
                    'contract' => (string) $contract,
                    'serviceId' => (string) $serviceId,
                    'className' => (string) $className,
                ];
            }
        }

        $container->setParameter('fabryq.capability_providers', $providers);
        $container->setParameter('fabryq.capability_provider_issues', $issues);
    }
}
