<?php

/**
 * Analyzer that reports missing capability providers per app.
 *
 * @package   Fabryq\Cli\Analyzer
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Analyzer;

use Fabryq\Cli\Report\Finding;
use Fabryq\Cli\Report\FindingLocation;
use Fabryq\Runtime\Attribute\FabryqProvider;
use Fabryq\Runtime\Registry\AppRegistry;
use Fabryq\Runtime\Registry\CapabilityProviderRegistry;

/**
 * Evaluates provider wiring and emits findings with per-app status.
 */
final readonly class Doctor
{
    /**
 * @param AppRegistry                $appRegistry      Registry of discovered apps.
 * @param CapabilityProviderRegistry $providerRegistry Registry of capability providers.
 * @param array<string, mixed>       $capabilityMap    Capability resolver map.
 */
    public function __construct(
        /**
         * Registry of application definitions.
         *
         * @var AppRegistry
         */
        private AppRegistry                $appRegistry,
        /**
         * Registry of capability providers and validation issues.
         *
         * @var CapabilityProviderRegistry
         */
        private CapabilityProviderRegistry $providerRegistry,
        /**
         * Capability resolver map from the container.
         *
         * @var array<string, mixed>
         */
        private array                      $capabilityMap,
    ) {}

    /**
     * Run the doctor checks and aggregate findings and app status.
     *
     * @return DoctorResult Result containing findings and app status metadata.
     */
    public function run(): DoctorResult
    {
        $findings = [];
        $appStatuses = [];

        foreach ($this->providerRegistry->getIssues() as $issue) {
            $findings[] = new Finding(
                $issue->ruleKey,
                'BLOCKER',
                $issue->message,
                new FindingLocation($issue->file, $issue->line, $issue->symbol)
            );
        }

        if ($this->capabilityMap === [] && $this->appRegistry->getApps() !== []) {
            $findings[] = new Finding(
                'FABRYQ.CAPABILITY.MAP.MISSING',
                'BLOCKER',
                'Capability resolver map is missing.',
                null
            );
        }

        foreach ($this->appRegistry->getApps() as $app) {
            $missingRequired = [];
            $missingOptional = [];
            $degraded = [];

            foreach ($app->manifest->consumes as $consume) {
                $entry = $this->capabilityMap[$consume->capabilityId] ?? null;
                $winner = is_array($entry) ? ($entry['winner'] ?? null) : null;

                if ($winner === null) {
                    if ($consume->required) {
                        $missingRequired[] = $consume->capabilityId;
                        $findings[] = new Finding(
                            'FABRYQ.CONSUME.REQUIRED.MISSING_PROVIDER',
                            'BLOCKER',
                            sprintf('Required capability "%s" has no provider.', $consume->capabilityId),
                            new FindingLocation($app->manifestPath, null, $consume->capabilityId)
                        );
                    } else {
                        $missingOptional[] = $consume->capabilityId;
                        $findings[] = new Finding(
                            'FABRYQ.CONSUME.OPTIONAL.MISSING_PROVIDER',
                            'WARNING',
                            sprintf('Optional capability "%s" has no provider.', $consume->capabilityId),
                            new FindingLocation($app->manifestPath, null, $consume->capabilityId)
                        );
                    }
                    continue;
                }

                if (is_array($winner) && isset($winner['priority']) && (int) $winner['priority'] === FabryqProvider::PRIORITY_DEGRADED) {
                    $degraded[] = $consume->capabilityId;
                }
            }

            $status = 'HEALTHY';
            if ($missingRequired !== [] || $missingOptional !== []) {
                $status = 'UNHEALTHY';
            } elseif ($degraded !== []) {
                $status = 'DEGRADED';
            }

            $appStatuses[$app->manifest->appId] = [
                'status' => $status,
                'missingRequired' => $missingRequired,
                'missingOptional' => $missingOptional,
                'degraded' => $degraded,
            ];
        }

        return new DoctorResult($appStatuses, $findings);
    }
}
