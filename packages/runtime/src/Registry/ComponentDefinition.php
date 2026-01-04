<?php

/**
 * Registry entry representing a discovered component.
 *
 * @package   Fabryq\Runtime\Registry
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Runtime\Registry;

/**
 * Immutable definition of a component within an application.
 */
final readonly class ComponentDefinition
{
    /**
     * @param string $name Component folder name.
     * @param string $slug URL-safe component slug.
     * @param string $path Absolute component path.
     */
    public function __construct(
        /**
         * Component name as defined by the folder.
         *
         * @var string
         */
        public string $name,
        /**
         * Normalized slug for routing and identifiers.
         *
         * @var string
         */
        public string $slug,
        /**
         * Absolute path to the component directory.
         *
         * @var string
         */
        public string $path,
    ) {
    }
}
