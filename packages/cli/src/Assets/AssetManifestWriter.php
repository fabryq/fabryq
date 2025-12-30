<?php

/**
 * Writer for asset installation manifests.
 *
 * @package Fabryq\Cli\Assets
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Assets;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Writes asset manifests to JSON and Markdown files.
 */
final class AssetManifestWriter
{
    /**
     * @param Filesystem $filesystem Filesystem abstraction for writing manifests.
     * @param string $projectDir Absolute project directory.
     */
    public function __construct(
        /**
         * Filesystem abstraction used for writing.
         *
         * @var Filesystem
         */
        private readonly Filesystem $filesystem,
        /**
         * Absolute project directory.
         *
         * @var string
         */
        private readonly string $projectDir,
    ) {
    }

    /**
     * Write the asset manifest files to disk.
     *
     * Side effects:
     * - Creates directories and writes JSON/Markdown files.
     *
     * @param AssetInstallResult $result Installation result to serialize.
     *
     * @throws \Symfony\Component\Filesystem\Exception\IOExceptionInterface When filesystem operations fail.
     */
    public function write(AssetInstallResult $result): void
    {
        $payload = [
            'generatedAt' => date('c'),
            'entries' => $result->entries,
            'collisions' => $result->collisions,
        ];

        $jsonPath = $this->projectDir.'/state/assets/manifest.json';
        $mdPath = $this->projectDir.'/state/assets/latest.md';

        $this->filesystem->mkdir(dirname($jsonPath));
        $this->filesystem->mkdir(dirname($mdPath));

        file_put_contents($jsonPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        file_put_contents($mdPath, $this->renderMarkdown($payload));
    }

    /**
     * Render a Markdown document for the asset manifest payload.
     *
     * @param array<string, mixed> $payload Manifest payload data.
     *
     * @return string Markdown document contents.
     */
    private function renderMarkdown(array $payload): string
    {
        $lines = [];
        $lines[] = '# Fabryq Assets Manifest';
        $lines[] = '';
        $lines[] = 'Generated: '.$payload['generatedAt'];
        $lines[] = '';

        if ($payload['collisions'] !== []) {
            $lines[] = '## Collisions';
            $lines[] = '';
            foreach ($payload['collisions'] as $collision) {
                $lines[] = '- '.$collision['target'];
                foreach ($collision['sources'] as $source) {
                    $lines[] = '  - '.$source;
                }
            }
            $lines[] = '';
        }

        $lines[] = '## Entries';
        $lines[] = '';
        if ($payload['entries'] === []) {
            $lines[] = 'No assets found.';
            $lines[] = '';
            return implode("\n", $lines);
        }

        $lines[] = '| Type | App | Component | Method | Target |';
        $lines[] = '| --- | --- | --- | --- | --- |';
        foreach ($payload['entries'] as $entry) {
            $lines[] = sprintf(
                '| %s | %s | %s | %s | %s |',
                $entry['type'],
                $entry['appId'],
                $entry['componentSlug'],
                $entry['method'],
                $entry['target']
            );
        }
        $lines[] = '';

        return implode("\n", $lines);
    }
}
