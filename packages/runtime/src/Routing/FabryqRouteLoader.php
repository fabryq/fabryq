<?php

/**
 * Route loader for Fabryq application components.
 *
 * @package   Fabryq\Runtime\Routing
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Runtime\Routing;

use Fabryq\Runtime\Registry\AppRegistry;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Loader\AttributeDirectoryLoader;
use Symfony\Component\Routing\RouteCollection;

/**
 * Loads attribute-based routes for all discovered components.
 */
final class FabryqRouteLoader extends Loader
{
    /**
     * Whether the routes have already been loaded.
     *
     * @var bool
     */
    private bool $loaded = false;

    /**
     * @param AppRegistry              $appRegistry              Registry of discovered applications.
     * @param AttributeDirectoryLoader $attributeDirectoryLoader Loader for attribute-based routes.
     */
    public function __construct(
        /**
         * Registry of applications and their components.
         *
         * @var AppRegistry
         */
        private readonly AppRegistry              $appRegistry,
        /**
         * Loader that scans directories for route attributes.
         *
         * @var AttributeDirectoryLoader
         */
        private readonly AttributeDirectoryLoader $attributeDirectoryLoader,
    ) {
        parent::__construct();
    }

    /**
     * {@inheritDoc}
     *
     * Side effects:
     * - Marks the loader as loaded to prevent duplicate registration.
     *
     * @param mixed       $resource Ignored by this loader.
     * @param string|null $type     [Optional] Loader type hint.
     *
     * @throws \RuntimeException When routes are loaded more than once.
     */
    public function load(mixed $resource, ?string $type = null): RouteCollection
    {
        if ($this->loaded) {
            throw new \RuntimeException('Fabryq routes already loaded.');
        }
        $this->loaded = true;

        $collection = new RouteCollection();

        foreach ($this->appRegistry->getApps() as $app) {
            $mountpoint = $app->manifest->mountpoint;
            if ($mountpoint === null) {
                continue;
            }

            foreach ($app->components as $component) {
                $controllerDir = $component->path . '/Controller';
                if (!is_dir($controllerDir)) {
                    continue;
                }

                $routes = $this->attributeDirectoryLoader->load($controllerDir, 'attribute');
                if (!$routes instanceof RouteCollection) {
                    continue;
                }
                $routes->addPrefix($mountpoint);
                $routes->addNamePrefix($app->manifest->appId . '.' . $component->slug . '.');

                $collection->addCollection($routes);
            }
        }

        return $collection;
    }

    /**
     * {@inheritDoc}
     *
     * @param mixed       $resource Ignored by this loader.
     * @param string|null $type     [Optional] Loader type hint.
     */
    public function supports(mixed $resource, ?string $type = null): bool
    {
        return $type === 'fabryq';
    }
}
