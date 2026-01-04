<?php

/**
 * Asset installer that publishes app and component assets.
 *
 * @package   Fabryq\Cli\Assets
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Assets;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Publishes assets from app/component resource directories.
 */
final readonly class AssetInstaller
{
    /**
     * @param Filesystem   $filesystem Filesystem abstraction for publishing assets.
     * @param AssetScanner $scanner    Asset scanner for asset sources and targets.
     */
    public function __construct(
        /**
         * Filesystem abstraction used for publishing.
         *
         * @var Filesystem
         */
        private Filesystem   $filesystem,
        /**
         * Scanner that discovers assets and collisions.
         *
         * @var AssetScanner
         */
        private AssetScanner $scanner,
    ) {
    }

    /**
     * Install assets into the public directory.
     *
     * Side effects:
     * - Creates directories, symlinks, or copies assets to public targets.
     *
     * @return AssetInstallResult Published entries and collisions.
     * @throws \Symfony\Component\Filesystem\Exception\IOExceptionInterface When filesystem operations fail.
     *
     */
    public function install(): AssetInstallResult
    {
        $scanResult = $this->scanner->scan();
        $entries = [];

        foreach ($scanResult->entries as $entry) {
            $entry['method'] = 'pending';
            $entries[] = $entry;
        }

        if ($scanResult->collisions === []) {
            foreach ($entries as &$entry) {
                $entry['method'] = $this->publish($entry['source'], $entry['target']);
            }
            unset($entry);
        }

        return new AssetInstallResult($entries, $scanResult->collisions);
    }

    /**
     * Publish a source path to a target via symlink or copy.
     *
     * Side effects:
     * - Removes existing targets and writes to disk.
     *
     * @param string $source Source path on disk.
     * @param string $target Target path on disk.
     *
     * @return string Publication method used ("symlink" or "copy").
     * @throws \Symfony\Component\Filesystem\Exception\IOExceptionInterface When filesystem operations fail.
     *
     */
    private function publish(string $source, string $target): string
    {
        if ($this->filesystem->exists($target)) {
            $this->filesystem->remove($target);
        }

        $this->filesystem->mkdir(dirname($target));

        try {
            $this->filesystem->symlink($source, $target);
            return 'symlink';
        } catch (\Throwable $exception) {
            if (is_dir($source)) {
                $this->filesystem->mirror($source, $target);
            } else {
                $this->filesystem->copy($source, $target);
            }
            return 'copy';
        }
    }
}
