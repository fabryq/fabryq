<?php

/**
 * Asset scanner for Fabryq applications and components.
 *
 * @package   Fabryq\Cli\Assets
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Assets;

use Fabryq\Runtime\Registry\AppRegistry;
use Fabryq\Runtime\Util\ComponentSlugger;

/**
 * Discovers asset directories for apps and components in the project.
 *
 * Responsibilities:
 * - Build a list of asset sources and their target publish locations.
 * - Detect collisions where multiple sources map to the same target.
 *
 * @phpstan-import-type AssetEntry from AssetScanResult
 * @phpstan-import-type AssetCollision from AssetScanResult
 */
final readonly class AssetScanner
{
    /**
     * @param AppRegistry      $appRegistry Registry of discovered applications.
     * @param ComponentSlugger $slugger     Slug generator for component names.
     * @param string           $projectDir  Absolute project directory.
     */
    public function __construct(
        /**
         * Registry of applications discovered by the runtime.
         *
         * @var AppRegistry
         */
        private AppRegistry      $appRegistry,
        /**
         * Slug generator used to normalize component names.
         *
         * @var ComponentSlugger
         */
        private ComponentSlugger $slugger,
        /**
         * Absolute project directory used to resolve asset paths.
         *
         * @var string
         */
        private string           $projectDir,
    ) {
    }

    /**
     * Scan the project for application and component asset directories.
     *
     * Side effects:
     * - Reads the filesystem to detect asset directories.
     *
     * @return AssetScanResult Asset entries and collision details.
     */
    public function scan(): AssetScanResult
    {
        /** @var list<AssetEntry> $entries */
        $entries = [];
        /** @var list<AssetCollision> $collisions */
        $collisions = [];
        /** @var array<string, list<string>> $targets */
        $targets = [];

        foreach ($this->appRegistry->getApps() as $app) {
            $appId = $app->manifest->appId;
            $appSource = $app->path . '/Resources/public';
            if (is_dir($appSource)) {
                $target = $this->projectDir . '/public/fabryq/apps/' . $appId;
                $entries[] = $this->buildEntry('app', $appId, null, $appSource, $target);
                $targets[$target][] = $appSource;
            }

            foreach ($app->components as $component) {
                $componentSource = $component->path . '/Resources/public';
                if (!is_dir($componentSource)) {
                    continue;
                }

                $target = $this->projectDir . '/public/fabryq/apps/' . $appId . '/' . $component->slug;
                $entries[] = $this->buildEntry('app_component', $appId, $component->slug, $componentSource, $target);
                $targets[$target][] = $componentSource;
            }
        }

        $globalComponentsDir = $this->projectDir . '/src/Components';
        if (is_dir($globalComponentsDir)) {
            foreach (glob($globalComponentsDir . '/*', GLOB_ONLYDIR) ?: [] as $componentPath) {
                $name = basename($componentPath);
                $slug = $this->slugger->slug($name);
                $source = $componentPath . '/Resources/public';
                if (!is_dir($source)) {
                    continue;
                }
                $target = $this->projectDir . '/public/fabryq/components/' . $slug;
                $entries[] = $this->buildEntry('global_component', null, $slug, $source, $target);
                $targets[$target][] = $source;
            }
        }

        foreach ($targets as $target => $sources) {
            if (count($sources) > 1) {
                $collisions[] = [
                    'target' => $target,
                    'sources' => $sources,
                ];
            }
        }

        return new AssetScanResult($entries, $collisions);
    }

    /**
     * Build a normalized asset entry payload.
     *
     * @param string      $type          Asset type identifier.
     * @param string|null $appId         [Optional] Application identifier, if scoped to an app.
     * @param string|null $componentSlug [Optional] Component slug when scoped to a component.
     * @param string      $source        Absolute source directory path.
     * @param string      $target        Absolute target directory path.
     *
     * @return AssetEntry Asset entry payload.
     */
    private function buildEntry(string $type, ?string $appId, ?string $componentSlug, string $source, string $target): array
    {
        return [
            'type' => $type,
            'appId' => $appId ?? '',
            'componentSlug' => $componentSlug ?? '',
            'source' => $source,
            'target' => $target,
        ];
    }
}
