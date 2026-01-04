<?php

/**
 * Registry entry representing a discovered application.
 *
 * @package   Fabryq\Runtime\Registry
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Runtime\Registry;

use Fabryq\Contracts\Manifest;

/**
 * Immutable definition of an application and its components.
 */
final readonly class AppDefinition
{
    /**
     * @param Manifest              $manifest     Parsed application manifest.
     * @param string                $path         Absolute application path.
     * @param string                $manifestPath Absolute manifest file path.
     * @param ComponentDefinition[] $components   Discovered component definitions.
     */
    public function __construct(
        /**
         * Parsed manifest metadata.
         *
         * @var Manifest
         */
        public Manifest $manifest,
        /**
         * Absolute path to the application root directory.
         *
         * @var string
         */
        public string   $path,
        /**
         * Absolute path to the manifest file.
         *
         * @var string
         */
        public string   $manifestPath,
        /**
         * Components discovered under the application.
         *
         * @var ComponentDefinition[]
         */
        public array    $components,
    ) {
    }
}
