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
use RuntimeException;

/**
 * Discovers Doctrine mapping and migration paths from app components.
 */
final readonly class DoctrineDiscovery
{
    /**
     * @var string
     */
    private string $namespaceRoot;

    /**
     * @param AppRegistry $appRegistry Registry of discovered applications.
     * @param string      $projectDir  Project root directory.
     */
    public function __construct(
        /**
         * Registry of applications used for discovery.
         *
         * @var AppRegistry
         */
        private AppRegistry $appRegistry,
        /**
         * Project root directory.
         *
         * @var string
         */
        string $projectDir
    ) {
        $this->namespaceRoot = $this->resolveNamespaceRoot($projectDir);
    }

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
                $prefix = $this->buildNamespacePrefix($appNamespace, $component->name, 'Entity');
                $mappings[$mappingKey] = [
                    'is_bundle' => false,
                    'type' => 'attribute',
                    'dir' => $entityDir,
                    'prefix' => $prefix,
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
                $namespace = $this->buildNamespacePrefix($appNamespace, $component->name, 'Migrations');
                $paths[$namespace] = $migrationDir;
            }
        }

        return $paths;
    }

    /**
     * Resolve the namespace root from composer.json autoload settings.
     *
     * @param string $projectDir Project root directory.
     *
     * @return string Namespace root without trailing backslashes.
     */
    private function resolveNamespaceRoot(string $projectDir): string
    {
        $composerPath = rtrim($projectDir, '/') . '/composer.json';
        if (!is_file($composerPath)) {
            throw new RuntimeException('composer.json not found while resolving app namespace.');
        }

        $data = json_decode((string)file_get_contents($composerPath), true);
        if (!is_array($data)) {
            throw new RuntimeException('composer.json is invalid JSON while resolving app namespace.');
        }

        $autoload = $data['autoload']['psr-4'] ?? [];
        if (!is_array($autoload)) {
            throw new RuntimeException('composer.json autoload.psr-4 must be an object.');
        }

        foreach ($autoload as $prefix => $paths) {
            foreach ((array)$paths as $path) {
                $normalized = rtrim(str_replace('\\', '/', (string)$path), '/');
                if ($normalized === 'src/Apps') {
                    return rtrim((string)$prefix, '\\');
                }
            }
        }

        throw new RuntimeException('Unable to resolve app namespace: expected autoload.psr-4 mapping for src/Apps/.');
    }

    /**
     * Build the full namespace prefix for a component sub-namespace.
     *
     * @param string $appNamespace App folder namespace segment.
     * @param string $component    Component name segment.
     * @param string $suffix       Namespace suffix segment.
     *
     * @return string
     */
    private function buildNamespacePrefix(string $appNamespace, string $component, string $suffix): string
    {
        $root = $this->namespaceRoot;
        $prefix = $root !== '' ? $root . '\\' : '';

        return $prefix . $appNamespace . '\\' . $component . '\\' . $suffix;
    }
}
