<?php

/**
 * Analyzer that builds capability graphs for applications.
 *
 * @package   Fabryq\Cli\Analyzer
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Analyzer;

use Fabryq\Runtime\Attribute\FabryqProvider;
use Fabryq\Runtime\Registry\AppRegistry;

/**
 * Builds a graph of consumed and provided capabilities per app.
 */
final readonly class GraphBuilder
{
    /**
     * @param AppRegistry          $appRegistry   Registry of discovered apps.
     * @param array<string, mixed> $capabilityMap Capability resolver map.
     */
    public function __construct(
        /**
         * Registry of application definitions.
         *
         * @var AppRegistry
         */
        private AppRegistry          $appRegistry,
        /**
         * Capability resolver map.
         *
         * @var array<string, mixed>
         */
        private array                $capabilityMap,
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
            foreach ($app->manifest->consumes as $consume) {
                $entry = $this->capabilityMap[$consume->capabilityId] ?? null;
                $winner = is_array($entry) ? ($entry['winner'] ?? null) : null;
                $providers = is_array($entry) ? ($entry['providers'] ?? []) : [];
                $contract = $consume->contract ?? ($entry['contract'] ?? null);
                $degraded = is_array($winner) && isset($winner['priority']) && (int) $winner['priority'] === FabryqProvider::PRIORITY_DEGRADED;
                $consumes[] = [
                    'capabilityId' => $consume->capabilityId,
                    'required' => $consume->required,
                    'contract' => $contract,
                    'providers' => $providers,
                    'winner' => $winner,
                    'degraded' => $degraded,
                ];
            }

            $graph[$app->manifest->appId] = [
                'consumes' => $consumes,
            ];
        }

        return $graph;
    }
}
