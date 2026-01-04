<?php

/**
 * Discovered manifest metadata with file system context.
 *
 * @package   Fabryq\Runtime\Discovery
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Runtime\Discovery;

use Fabryq\Contracts\Manifest;

/**
 * Immutable container for a manifest and its discovery context.
 */
final readonly class DiscoveredManifest
{
    /**
     * @param Manifest $manifest     Parsed manifest value object.
     * @param string   $appPath      Absolute application path.
     * @param string   $manifestPath Absolute manifest file path.
     * @param string   $appFolder    Application folder name.
     */
    public function __construct(
        /**
         * Parsed manifest data.
         *
         * @var Manifest
         */
        public Manifest $manifest,
        /**
         * Absolute path to the application directory.
         *
         * @var string
         */
        public string   $appPath,
        /**
         * Absolute path to the manifest file.
         *
         * @var string
         */
        public string   $manifestPath,
        /**
         * Name of the application folder.
         *
         * @var string
         */
        public string   $appFolder,
    ) {
    }
}
