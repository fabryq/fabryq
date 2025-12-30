<?php

/**
 * Factory for building application registries from discovery services.
 *
 * @package   Fabryq\Runtime\Registry
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Runtime\Registry;

use Fabryq\Runtime\Discovery\ComponentDiscovery;
use Fabryq\Runtime\Discovery\ManifestDiscovery;

/**
 * Builds registries by discovering app manifests and components.
 */
final readonly class AppRegistryFactory
{
    /**
     * @param ManifestDiscovery  $manifestDiscovery  Manifest discovery service.
     * @param ComponentDiscovery $componentDiscovery Component discovery service.
     */
    public function __construct(
        /**
         * Service for finding and parsing app manifests.
         *
         * @var ManifestDiscovery
         */
        private ManifestDiscovery  $manifestDiscovery,
        /**
         * Service for discovering components within an app.
         *
         * @var ComponentDiscovery
         */
        private ComponentDiscovery $componentDiscovery,
    ) {}

    /**
     * Build an application registry for the given project directory.
     *
     * @param string $projectDir Absolute project directory.
     *
     * @return AppRegistry Registry populated with discovered apps and issues.
     */
    public function build(string $projectDir): AppRegistry
    {
        $result = $this->manifestDiscovery->discover($projectDir);
        $apps = [];

        foreach ($result['apps'] as $discovered) {
            $components = $this->componentDiscovery->discover($discovered->appPath);
            $apps[] = new AppDefinition(
                $discovered->manifest,
                $discovered->appPath,
                $discovered->manifestPath,
                $components
            );
        }

        return new AppRegistry($apps, $result['issues']);
    }
}
