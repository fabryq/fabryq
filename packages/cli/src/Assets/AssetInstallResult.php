<?php

/**
 * Result container for asset installation.
 *
 * @package   Fabryq\Cli\Assets
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Assets;

/**
 * Holds published asset entries and detected collisions.
 *
 * @phpstan-import-type AssetEntry from AssetScanResult
 * @phpstan-import-type AssetCollision from AssetScanResult
 * @phpstan-type AssetInstallEntry array{
 *   type: string,
 *   appId: string,
 *   componentSlug: string,
 *   source: string,
 *   target: string,
 *   method: string
 * }
 */
final readonly class AssetInstallResult
{
    /**
     * @param list<AssetInstallEntry> $entries    Published asset entries.
     * @param list<AssetCollision>    $collisions Collisions keyed by target.
     */
    public function __construct(
        /**
         * Published asset entries.
         *
         * @var list<AssetInstallEntry>
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
