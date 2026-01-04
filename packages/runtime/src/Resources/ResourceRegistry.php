<?php

/**
 * Resource registry for application configuration, templates, and translations.
 *
 * @package   Fabryq\Runtime\Resources
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Runtime\Resources;

use Fabryq\Runtime\Registry\AppRegistry;

/**
 * Collects resource paths for all discovered applications and components.
 */
final class ResourceRegistry
{
    /**
     * Allowed configuration filenames searched in resource directories.
     *
     * @var string[]
     */
    private const CONFIG_ALLOWLIST = [
        'services.yaml',
        'services.yml',
        'services.php',
        'routes.yaml',
        'routes.yml',
        'routes.php',
    ];

    /**
     * @param AppRegistry $appRegistry Registry of discovered applications.
     */
    public function __construct(
        /**
         * Registry used to locate app and component resource directories.
         *
         * @var AppRegistry
         */
        private readonly AppRegistry $appRegistry
    ) {
    }

    /**
     * Collect configuration files from app and component resource folders.
     *
     * @return string[]
     */
    public function getConfigFiles(): array
    {
        $files = [];
        foreach ($this->appRegistry->getApps() as $app) {
            $files = array_merge($files, $this->collectConfigFiles($app->path . '/Resources/config'));
            foreach ($app->components as $component) {
                $files = array_merge($files, $this->collectConfigFiles($component->path . '/Resources/config'));
            }
        }

        return $files;
    }

    /**
     * Collect template directories for all apps and components.
     *
     * @return string[]
     */
    public function getTemplatePaths(): array
    {
        $paths = [];
        foreach ($this->appRegistry->getApps() as $app) {
            $paths[] = $app->path . '/Resources/templates';
            foreach ($app->components as $component) {
                $paths[] = $component->path . '/Resources/templates';
            }
        }

        return array_values(array_filter($paths, 'is_dir'));
    }

    /**
     * Collect translation directories for all apps and components.
     *
     * @return string[]
     */
    public function getTranslationPaths(): array
    {
        $paths = [];
        foreach ($this->appRegistry->getApps() as $app) {
            $paths[] = $app->path . '/Resources/translations';
            foreach ($app->components as $component) {
                $paths[] = $component->path . '/Resources/translations';
            }
        }

        return array_values(array_filter($paths, 'is_dir'));
    }

    /**
     * Collect files from a config directory that match the allowlist.
     *
     * @param string $configDir Absolute configuration directory.
     *
     * @return string[]
     */
    private function collectConfigFiles(string $configDir): array
    {
        if (!is_dir($configDir)) {
            return [];
        }

        $files = [];
        foreach (self::CONFIG_ALLOWLIST as $filename) {
            $path = $configDir . '/' . $filename;
            if (is_file($path)) {
                $files[] = $path;
            }
        }

        return $files;
    }
}
