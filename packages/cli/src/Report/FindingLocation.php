<?php

/**
 * Location metadata for a report finding.
 *
 * @package   Fabryq\Cli\Report
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Report;

/**
 * Immutable source location details for a finding.
 */
final readonly class FindingLocation
{
    /**
     * @param string|null $file   [Optional] Source file path.
     * @param int|null    $line   [Optional] Line number within the file.
     * @param string|null $symbol [Optional] Symbol name or identifier.
     */
    public function __construct(
        /**
         * Source file path when known.
         *
         * @var string|null
         */
        public ?string $file,
        /**
         * Line number when known.
         *
         * @var int|null
         */
        public ?int    $line,
        /**
         * Symbol name when known.
         *
         * @var string|null
         */
        public ?string $symbol,
    ) {
    }
}
