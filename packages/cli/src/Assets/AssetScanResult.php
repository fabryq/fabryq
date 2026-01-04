<?php

/**
 * Scan result container for discovered assets.
 *
 * @package   Fabryq\Cli\Assets
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Assets;

/**
 * Captures asset scan entries and detected target collisions.
 */
final readonly class AssetScanResult
{
    /**
     * @param array<int, array<string, string>> $entries    Asset entries discovered during the scan.
     * @param array<int, array<string, mixed>>  $collisions Target collisions keyed by destination.
     */
    public function __construct(
        /**
         * Asset entries with source/target metadata.
         *
         * @var array<int, array<string, string>>
         */
        public array $entries,
        /**
         * Collision details when multiple sources map to the same target.
         *
         * @var array<int, array<string, mixed>>
         */
        public array $collisions,
    ) {
    }
}
