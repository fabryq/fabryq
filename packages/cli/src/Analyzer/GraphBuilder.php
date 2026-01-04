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
 *
 * @phpstan-import-type CapabilityMapEntry from \Fabryq\Runtime\DependencyInjection\Compiler\CapabilityProviderPass
 * @phpstan-type GraphProvider array{
 *   serviceId: string,
 *   className: class-string,
 *   priority: int,
 *   capability: string,
 *   winner: bool
 * }
 * @phpstan-type GraphWinner array{
 *   serviceId: string,
 *   className: class-string,
 *   priority: int,
 *   capability: string
 * }
 * @phpstan-type GraphConsume array{
 *   capabilityId: string,
 *   required: bool,
 *   contract: string|null,
 *   providers: list<GraphProvider>,
 *   winner: GraphWinner|null,
 *   degraded: bool
 * }
 * @phpstan-type GraphApp array{consumes: list<GraphConsume>}
 * @phpstan-type GraphPayload array<string, GraphApp>
 */
final readonly class GraphBuilder
{
    /**
     * @param AppRegistry          $appRegistry   Registry of discovered apps.
     * @param array<string, CapabilityMapEntry> $capabilityMap Capability resolver map.
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
         * @var array<string, CapabilityMapEntry>
         */
        private array                $capabilityMap,
    ) {
    }

    /**
     * Build a capability graph for all discovered apps.
     *
     * @return GraphPayload
     */
    public function build(): array
    {
        $graph = [];

        foreach ($this->appRegistry->getApps() as $app) {
            $consumes = [];
            foreach ($app->manifest->consumes as $consume) {
                $entry = $this->capabilityMap[$consume->capabilityId] ?? null;
                if ($entry === null) {
                    $winner = null;
                    $providers = [];
                    $contract = $consume->contract;
                } else {
                    $winner = $entry['winner'];
                    $providers = $entry['providers'];
                    $contract = $consume->contract ?? $entry['contract'];
                }
                $degraded = $winner !== null && $winner['priority'] === FabryqProvider::PRIORITY_DEGRADED;
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
