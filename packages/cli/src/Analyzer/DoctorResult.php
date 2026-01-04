<?php

/**
 * Result container for the doctor analyzer.
 *
 * @package   Fabryq\Cli\Analyzer
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Analyzer;

use Fabryq\Cli\Report\Finding;

/**
 * Holds app status details and findings from a doctor run.
 *
 * @phpstan-type AppStatus array{
 *   status: string,
 *   missingRequired: list<string>,
 *   missingOptional: list<string>,
 *   degraded: list<string>
 * }
 */
final readonly class DoctorResult
{
    /**
     * @param array<string, AppStatus> $appStatuses Per-app status data.
     * @param Finding[]                           $findings    Findings emitted by the doctor checks.
     */
    public function __construct(
        /**
         * Map of app IDs to status metadata.
         *
         * @var array<string, AppStatus>
         */
        public array $appStatuses,
        /**
         * Findings emitted during analysis.
         *
         * @var Finding[]
         */
        public array $findings,
    ) {
    }
}
