<?php

declare(strict_types=1);

namespace Fabryq\Cli\Assets;

final class AssetScanResult
{
    /**
     * @param array<int, array<string, string>> $entries
     * @param array<int, array<string, mixed>> $collisions
     */
    public function __construct(
        public readonly array $entries,
        public readonly array $collisions,
    ) {
    }
}
