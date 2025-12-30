<?php

/**
 * Result container for asset installation.
 *
 * @package Fabryq\Cli\Assets
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Assets;

/**
 * Holds published asset entries and detected collisions.
 */
final class AssetInstallResult
{
    /**
     * @param array<int, array<string, string>> $entries Published asset entries.
     * @param array<int, array<string, mixed>> $collisions Collisions keyed by target.
     */
    public function __construct(
        /**
         * Published asset entries.
         *
         * @var array<int, array<string, string>>
         */
        public readonly array $entries,
        /**
         * Collision details when multiple sources map to the same target.
         *
         * @var array<int, array<string, mixed>>
         */
        public readonly array $collisions,
    ) {
    }
}
