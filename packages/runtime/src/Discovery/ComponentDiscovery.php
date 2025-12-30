<?php

/**
 * Component discovery for applications.
 *
 * @package   Fabryq\Runtime\Discovery
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Runtime\Discovery;

use Fabryq\Runtime\Registry\ComponentDefinition;
use Fabryq\Runtime\Util\ComponentSlugger;

/**
 * Discovers components under an application path.
 */
final readonly class ComponentDiscovery
{
    /**
     * @param ComponentSlugger $slugger Slug generator for component names.
     */
    public function __construct(
        /**
         * Slug generator used for component identifiers.
         *
         * @var ComponentSlugger
         */
        private ComponentSlugger $slugger
    ) {}

    /**
     * Discover components under an application directory.
     *
     * @param string $appPath Absolute application path.
     *
     * @return ComponentDefinition[]
     */
    public function discover(string $appPath): array
    {
        if (!is_dir($appPath)) {
            return [];
        }

        $components = [];
        $iterator = new \DirectoryIterator($appPath);
        foreach ($iterator as $entry) {
            if ($entry->isDot() || !$entry->isDir()) {
                continue;
            }

            $name = $entry->getBasename();
            if (in_array($name, ['Resources', 'Doc'], true)) {
                continue;
            }

            $components[] = new ComponentDefinition(
                $name,
                $this->slugger->slug($name),
                $entry->getPathname()
            );
        }

        return $components;
    }
}
