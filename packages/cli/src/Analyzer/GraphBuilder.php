<?php

/**
 * Analyzer that builds capability graphs for applications.
 *
 * @package   Fabryq\Cli\Analyzer
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Analyzer;

use Fabryq\Runtime\Registry\AppRegistry;
use Fabryq\Runtime\Registry\CapabilityProviderRegistry;

/**
 * Builds a graph of consumed and provided capabilities per app.
 */
final readonly class GraphBuilder
{
    /**
     * @param AppRegistry                $appRegistry      Registry of discovered apps.
     * @param CapabilityProviderRegistry $providerRegistry Registry of capability providers.
     */
    public function __construct(
        /**
         * Registry of application definitions.
         *
         * @var AppRegistry
         */
        private AppRegistry                $appRegistry,
        /**
         * Registry of capability providers.
         *
         * @var CapabilityProviderRegistry
         */
        private CapabilityProviderRegistry $providerRegistry,
    ) {}

    /**
     * Build a capability graph for all discovered apps.
     *
     * @return array<string, array<string, mixed>>
     */
    public function build(): array
    {
        $graph = [];

        foreach ($this->appRegistry->getApps() as $app) {
            $consumes = [];
            $provides = [];

            foreach ($app->manifest->consumes as $consume) {
                $provider = $this->providerRegistry->findByCapabilityId($consume->capabilityId);
                $consumes[] = [
                    'capabilityId' => $consume->capabilityId,
                    'required' => $consume->required,
                    'contract' => $consume->contract,
                    'provider' => $provider?->className,
                ];

                if ($provider !== null) {
                    $provides[$provider->capabilityId] = [
                        'capabilityId' => $provider->capabilityId,
                        'contract' => $provider->contract,
                        'provider' => $provider->className,
                    ];
                }
            }

            $graph[$app->manifest->appId] = [
                'consumes' => $consumes,
                'provides' => array_values($provides),
            ];
        }

        return $graph;
    }
}
