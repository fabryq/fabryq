<?php

declare(strict_types=1);

namespace Fabryq\Cli\Assets;

final readonly class AssetScanResult
{
    /**
     * @param array<int, array<string, string>> $entries
     * @param array<int, array<string, mixed>>  $collisions
     */
    public function __construct(
        public array $entries,
        public array $collisions,
    ) {}
}
