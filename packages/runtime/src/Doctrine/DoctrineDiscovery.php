<?php

/**
 * Doctrine integration discovery for app entities and migrations.
 *
 * @package   Fabryq\Runtime\Doctrine
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Runtime\Doctrine;

use Fabryq\Runtime\Registry\AppRegistry;

/**
 * Discovers Doctrine mapping and migration paths from app components.
 */
final readonly class DoctrineDiscovery
{
    /**
     * @param AppRegistry $appRegistry Registry of discovered applications.
     */
    public function __construct(
        /**
         * Registry of applications used for discovery.
         *
         * @var AppRegistry
         */
        private AppRegistry $appRegistry
    ) {}

    /**
     * Build Doctrine ORM mapping configuration for entity directories.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getEntityMappings(): array
    {
        $mappings = [];

        foreach ($this->appRegistry->getApps() as $app) {
            foreach ($app->components as $component) {
                $entityDir = $component->path . '/Entity';
                if (!is_dir($entityDir)) {
                    continue;
                }

                $mappingKey = sprintf('fabryq_%s_%s', $app->manifest->appId, $component->slug);
                $appNamespace = basename($app->path);
                $mappings[$mappingKey] = [
                    'is_bundle' => false,
                    'type' => 'attribute',
                    'dir' => $entityDir,
                    'prefix' => sprintf('App\\%s\\%s\\Entity', $appNamespace, $component->name),
                ];
            }
        }

        return $mappings;
    }

    /**
     * Build Doctrine migration paths for discovered components.
     *
     * @return array<string, string>
     */
    public function getMigrationPaths(): array
    {
        $paths = [];

        foreach ($this->appRegistry->getApps() as $app) {
            foreach ($app->components as $component) {
                $migrationDir = $component->path . '/Resources/migrations';
                if (!is_dir($migrationDir)) {
                    continue;
                }

                $appNamespace = basename($app->path);
                $namespace = sprintf('App\\%s\\%s\\Migrations', $appNamespace, $component->name);
                $paths[$namespace] = $migrationDir;
            }
        }

        return $paths;
    }
}
