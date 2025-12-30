<?php

/**
 * Validation issue captured during discovery or registry building.
 *
 * @package Fabryq\Runtime\Registry
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Runtime\Registry;

/**
 * Immutable validation issue with optional source metadata.
 */
final class ValidationIssue
{
    /**
     * @param string $ruleKey Validation rule identifier.
     * @param string $message Human-readable issue description.
     * @param string|null $file [Optional] Source file path when available.
     * @param int|null $line [Optional] Source line number when available.
     * @param string|null $symbol [Optional] Affected symbol when available.
     */
    public function __construct(
        /**
         * Validation rule identifier.
         *
         * @var string
         */
        public readonly string $ruleKey,
        /**
         * Human-readable message describing the issue.
         *
         * @var string
         */
        public readonly string $message,
        /**
         * Source file path, if known.
         *
         * @var string|null
         */
        public readonly ?string $file = null,
        /**
         * Source line number, if known.
         *
         * @var int|null
         */
        public readonly ?int $line = null,
        /**
         * Source symbol, if known.
         *
         * @var string|null
         */
        public readonly ?string $symbol = null,
    ) {
    }
}
