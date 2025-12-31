<?php

/**
 * Analyzer that runs verification gates for Fabryq projects.
 *
 * @package   Fabryq\Cli\Analyzer
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Analyzer;

use Fabryq\Cli\Assets\AssetScanner;
use Fabryq\Cli\Gate\DoctrineGate;
use Fabryq\Cli\Report\Finding;
use Fabryq\Cli\Report\FindingLocation;
use Fabryq\Runtime\Registry\AppRegistry;
use Fabryq\Runtime\Registry\CapabilityProviderRegistry;
use Fabryq\Runtime\Util\ComponentSlugger;

/**
 * Aggregates verification checks and returns findings.
 */
final readonly class Verifier
{
    /**
     * @param AppRegistry                $appRegistry      Registry of discovered apps.
     * @param CapabilityProviderRegistry $providerRegistry Registry of capability providers.
     * @param ComponentSlugger           $slugger          Slug generator for component names.
     * @param AssetScanner               $assetScanner     Scanner for asset collisions.
     * @param CrossAppReferenceScanner   $crossAppScanner  Scanner for cross-app references.
     * @param ServiceLocatorScanner      $serviceLocatorScanner Scanner for service locator usage.
     * @param EntityBaseScanner          $entityBaseScanner Scanner for entity base usage.
     * @param DoctrineGate               $doctrineGate     Doctrine validation gate.
     */
    public function __construct(
        /**
         * Registry of applications.
         *
         * @var AppRegistry
         */
        private AppRegistry                $appRegistry,
        /**
         * Registry of capability providers.
         *
         * @var CapabilityProviderRegistry
         */
        private CapabilityProviderRegistry $providerRegistry,
        /**
         * Slug generator used for component validation.
         *
         * @var ComponentSlugger
         */
        private ComponentSlugger           $slugger,
        /**
         * Scanner for asset collisions.
         *
         * @var AssetScanner
         */
        private AssetScanner               $assetScanner,
        /**
         * Scanner for invalid cross-app references.
         *
         * @var CrossAppReferenceScanner
         */
        private CrossAppReferenceScanner   $crossAppScanner,
        /**
         * Scanner for forbidden service locator usage.
         *
         * @var ServiceLocatorScanner
         */
        private ServiceLocatorScanner      $serviceLocatorScanner,
        /**
         * Scanner for entity base usage.
         *
         * @var EntityBaseScanner
         */
        private EntityBaseScanner          $entityBaseScanner,
        /**
         * Doctrine validation gate.
         *
         * @var DoctrineGate
         */
        private DoctrineGate               $doctrineGate,
    ) {}

    /**
     * Run all verification checks for the project.
     *
     * Side effects:
     * - Reads files from disk for analysis.
     *
     * @param string $projectDir Absolute project directory.
     *
     * @return Finding[]
     */
    public function verify(string $projectDir): array
    {
        $findings = [];

        foreach ($this->appRegistry->getIssues() as $issue) {
            $findings[] = new Finding(
                $issue->ruleKey,
                'BLOCKER',
                $issue->message,
                new FindingLocation($issue->file, $issue->line, $issue->symbol)
            );
        }

        $findings = array_merge($findings, $this->checkComponentSlugs());
        $findings = array_merge($findings, $this->checkGlobalComponentSlugs($projectDir));
        $findings = array_merge($findings, $this->checkCapabilityIds());
        $findings = array_merge($findings, $this->crossAppScanner->scan($projectDir));
        $findings = array_merge($findings, $this->serviceLocatorScanner->scan($projectDir));
        $findings = array_merge($findings, $this->entityBaseScanner->scan($projectDir));
        $findings = array_merge($findings, $this->checkAssetCollisions($projectDir));
        $findings = array_merge($findings, $this->doctrineGate->check($projectDir));
        $findings = array_merge($findings, $this->checkProviders());

        return $findings;
    }

    /**
     * Validate asset targets for collisions.
     *
     * @return Finding[]
     */
    private function checkAssetCollisions(string $projectDir): array
    {
        $findings = [];
        $result = $this->assetScanner->scan();

        foreach ($result->collisions as $collision) {
            $target = $this->normalizePath($collision['target'], $projectDir);
            $sources = array_map(
                fn(string $source) => $this->normalizePath($source, $projectDir),
                $collision['sources']
            );

            $findings[] = new Finding(
                'FABRYQ.PUBLIC.COLLISION',
                'BLOCKER',
                sprintf('Asset target "%s" has multiple sources: %s', $target, implode(', ', $sources)),
                new FindingLocation($target, null, implode(', ', $sources)),
                ['primary' => $target]
            );
        }

        return $findings;
    }

    /**
     * Normalize a path relative to the project directory.
     *
     * @param string $path       Absolute path.
     * @param string $projectDir Project root path.
     *
     * @return string Normalized relative path.
     */
    private function normalizePath(string $path, string $projectDir): string
    {
        $normalized = str_replace('\\\\', '/', $path);
        $projectDir = str_replace('\\\\', '/', $projectDir);
        if (str_starts_with($normalized, $projectDir . '/')) {
            $normalized = substr($normalized, strlen($projectDir) + 1);
        }

        return ltrim($normalized, '/');
    }

    /**
     * Validate capability identifiers for manifests and providers.
     *
     * @return Finding[]
     */
    private function checkCapabilityIds(): array
    {
        $findings = [];
        $pattern = '/^[a-z0-9]+(?:-[a-z0-9]+)*(?:\\.[a-z0-9]+(?:-[a-z0-9]+)*)+$/';

        foreach ($this->appRegistry->getApps() as $app) {
            foreach ($app->manifest->consumes as $consume) {
                if (preg_match($pattern, $consume->capabilityId)) {
                    continue;
                }

                $findings[] = new Finding(
                    'FABRYQ.CAPABILITY.ID.INVALID',
                    'WARNING',
                    sprintf('Capability id "%s" must be namespaced (example: fabryq.bridge.core.http-client).', $consume->capabilityId),
                    new FindingLocation($app->manifestPath, null, $consume->capabilityId)
                );
            }
        }

        foreach ($this->providerRegistry->getProviders() as $provider) {
            if (preg_match($pattern, $provider->capability)) {
                continue;
            }

            $findings[] = new Finding(
                'FABRYQ.CAPABILITY.ID.INVALID',
                'WARNING',
                sprintf('Capability id "%s" must be namespaced (example: fabryq.bridge.core.http-client).', $provider->capability),
                new FindingLocation(null, null, $provider->className)
            );
        }

        return $findings;
    }

    /**
     * Validate component slugs within each application.
     *
     * @return Finding[]
     */
    private function checkComponentSlugs(): array
    {
        $findings = [];

        foreach ($this->appRegistry->getApps() as $app) {
            $seen = [];
            foreach ($app->components as $component) {
                $slug = $component->slug;
                if ($slug === '' || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
                    $findings[] = new Finding(
                        'FABRYQ.COMPONENT.SLUG.INVALID',
                        'BLOCKER',
                        sprintf('Component "%s" has invalid slug "%s".', $component->name, $slug),
                        new FindingLocation($component->path, null, $component->slug)
                    );
                }

                $seen[$slug][] = $component->path;
            }

            foreach ($seen as $slug => $paths) {
                if (count($paths) > 1) {
                    $findings[] = new Finding(
                        'FABRYQ.COMPONENT.SLUG.COLLISION',
                        'BLOCKER',
                        sprintf('Component slug "%s" is used multiple times in app %s.', $slug, $app->manifest->appId),
                        new FindingLocation($app->manifestPath, null, $slug)
                    );
                }
            }
        }

        return $findings;
    }

    /**
     * Validate component slugs within the global components directory.
     *
     * @param string $projectDir Absolute project directory.
     *
     * @return Finding[]
     */
    private function checkGlobalComponentSlugs(string $projectDir): array
    {
        $findings = [];
        $componentsDir = $projectDir . '/src/Components';
        if (!is_dir($componentsDir)) {
            return $findings;
        }

        $seen = [];
        foreach (glob($componentsDir . '/*', GLOB_ONLYDIR) ?: [] as $componentPath) {
            $name = basename($componentPath);
            $slug = $this->slugger->slug($name);
            if ($slug === '' || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
                $findings[] = new Finding(
                    'FABRYQ.COMPONENT.SLUG.INVALID',
                    'BLOCKER',
                    sprintf('Component \"%s\" has invalid slug \"%s\".', $name, $slug),
                    new FindingLocation($componentPath, null, $slug)
                );
            }
            $seen[$slug][] = $componentPath;
        }

        foreach ($seen as $slug => $paths) {
            if (count($paths) > 1) {
                $findings[] = new Finding(
                    'FABRYQ.COMPONENT.SLUG.COLLISION',
                    'BLOCKER',
                    sprintf('Component slug \"%s\" is used multiple times in global components.', $slug),
                    new FindingLocation($componentsDir, null, $slug)
                );
            }
        }

        return $findings;
    }

    /**
     * Validate required capability providers for all apps.
     *
     * @return Finding[]
     */
    private function checkProviders(): array
    {
        $findings = [];

        foreach ($this->providerRegistry->getIssues() as $issue) {
            $findings[] = new Finding(
                $issue->ruleKey,
                'BLOCKER',
                $issue->message,
                new FindingLocation($issue->file, $issue->line, $issue->symbol)
            );
        }

        foreach ($this->appRegistry->getApps() as $app) {
            foreach ($app->manifest->consumes as $consume) {
                if (!$consume->required) {
                    continue;
                }

                if ($this->providerRegistry->findByCapability($consume->capabilityId) !== null) {
                    continue;
                }

                $findings[] = new Finding(
                    'FABRYQ.CONSUME.REQUIRED.MISSING_PROVIDER',
                    'BLOCKER',
                    sprintf('Required capability "%s" has no provider.', $consume->capabilityId),
                    new FindingLocation($app->manifestPath, null, $consume->capabilityId)
                );
            }
        }

        return $findings;
    }
}
