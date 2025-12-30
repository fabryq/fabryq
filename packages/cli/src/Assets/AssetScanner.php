<?php

declare(strict_types=1);

namespace Fabryq\Cli\Assets;

use Fabryq\Runtime\Registry\AppRegistry;
use Fabryq\Runtime\Util\ComponentSlugger;

final class AssetScanner
{
    public function __construct(
        private readonly AppRegistry $appRegistry,
        private readonly ComponentSlugger $slugger,
        private readonly string $projectDir,
    ) {
    }

    public function scan(): AssetScanResult
    {
        $entries = [];
        $collisions = [];
        $targets = [];

        foreach ($this->appRegistry->getApps() as $app) {
            $appId = $app->manifest->appId;
            $appSource = $app->path.'/Resources/public';
            if (is_dir($appSource)) {
                $target = $this->projectDir.'/public/fabryq/apps/'.$appId;
                $entries[] = $this->buildEntry('app', $appId, null, $appSource, $target);
                $targets[$target][] = $appSource;
            }

            foreach ($app->components as $component) {
                $componentSource = $component->path.'/Resources/public';
                if (!is_dir($componentSource)) {
                    continue;
                }

                $target = $this->projectDir.'/public/fabryq/apps/'.$appId.'/'.$component->slug;
                $entries[] = $this->buildEntry('app_component', $appId, $component->slug, $componentSource, $target);
                $targets[$target][] = $componentSource;
            }
        }

        $globalComponentsDir = $this->projectDir.'/src/Components';
        if (is_dir($globalComponentsDir)) {
            foreach (glob($globalComponentsDir.'/*', GLOB_ONLYDIR) ?: [] as $componentPath) {
                $name = basename($componentPath);
                $slug = $this->slugger->slug($name);
                $source = $componentPath.'/Resources/public';
                if (!is_dir($source)) {
                    continue;
                }
                $target = $this->projectDir.'/public/fabryq/components/'.$slug;
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
     * @return array<string, string>
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
