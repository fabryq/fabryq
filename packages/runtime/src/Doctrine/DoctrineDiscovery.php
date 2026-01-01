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
     * @param string      $projectDir  Project root for composer autoload inspection.
     */
    public function __construct(
        /**
         * Registry of applications used for discovery.
         *
         * @var AppRegistry
         */
        private AppRegistry $appRegistry,
        /**
         * Absolute project directory.
         *
         * @var string
         */
        private string      $projectDir,
    ) {}

    /**
     * Build Doctrine ORM mapping configuration for entity directories.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getEntityMappings(): array
    {
        $mappings = [];
        $prefix = $this->resolveAppNamespacePrefix();
        if ($prefix === null) {
            return $mappings;
        }

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
                    'prefix' => sprintf('%s\\%s\\%s\\Entity', $prefix, $appNamespace, $component->name),
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
        $prefix = $this->resolveAppNamespacePrefix();
        if ($prefix === null) {
            return $paths;
        }

        foreach ($this->appRegistry->getApps() as $app) {
            foreach ($app->components as $component) {
                $migrationDir = $component->path . '/Resources/migrations';
                if (!is_dir($migrationDir)) {
                    continue;
                }

                $appNamespace = basename($app->path);
                $namespace = sprintf('%s\\%s\\%s\\Migrations', $prefix, $appNamespace, $component->name);
                $paths[$namespace] = $migrationDir;
            }
        }

        return $paths;
    }

    /**
     * Resolve the namespace prefix that maps to src/Apps.
     *
     * @return string|null Namespace prefix without trailing backslash.
     */
    private function resolveAppNamespacePrefix(): ?string
    {
        $composerPath = $this->projectDir . '/composer.json';
        if (!is_file($composerPath)) {
            return null;
        }

        $data = json_decode((string)file_get_contents($composerPath), true);
        if (!is_array($data)) {
            return null;
        }

        $autoload = $data['autoload']['psr-4'] ?? [];
        if (!is_array($autoload)) {
            return null;
        }

        foreach ($autoload as $namespace => $paths) {
            $paths = is_array($paths) ? $paths : [$paths];
            foreach ($paths as $path) {
                if (!is_string($path)) {
                    continue;
                }
                $normalized = rtrim(str_replace('\\', '/', $path), '/') . '/';
                if ($normalized === 'src/Apps/') {
                    return rtrim((string)$namespace, '\\');
                }
            }
        }

        return null;
    }
}
