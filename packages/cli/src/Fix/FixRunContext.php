<?php

/**
 * Fix run context payload.
 *
 * @package Fabryq\Cli\Fix
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Fix;

/**
 * Holds metadata for a fix run.
 */
final readonly class FixRunContext
{
    /**
     * @param string $runId     Deterministic run identifier.
     * @param string $runDir    Absolute run directory path.
     * @param string $startedAt ISO-8601 start timestamp.
     */
    public function __construct(
        public string $runId,
        public string $runDir,
        public string $startedAt,
    ) {}
}
