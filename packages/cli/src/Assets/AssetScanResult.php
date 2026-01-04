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
 *
 * @phpstan-type AssetEntry array{
 *   type: string,
 *   appId: string,
 *   componentSlug: string,
 *   source: string,
 *   target: string
 * }
 * @phpstan-type AssetCollision array{target: string, sources: list<string>}
 */
final readonly class AssetScanResult
{
    /**
     * @param list<AssetEntry>     $entries    Asset entries discovered during the scan.
     * @param list<AssetCollision> $collisions Target collisions keyed by destination.
     */
    public function __construct(
        /**
         * Asset entries with source/target metadata.
         *
         * @var list<AssetEntry>
         */
        public array $entries,
        /**
         * Collision details when multiple sources map to the same target.
         *
         * @var list<AssetCollision>
         */
        public array $collisions,
    ) {
    }
}
