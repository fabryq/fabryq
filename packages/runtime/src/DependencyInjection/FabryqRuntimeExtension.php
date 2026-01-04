<?php

/**
 * Symfony DI extension for the Fabryq runtime bundle.
 *
 * @package   Fabryq\Runtime\DependencyInjection
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Runtime\DependencyInjection;

use Exception;
use Fabryq\Runtime\Attribute\FabryqProvider;
use Fabryq\Runtime\Discovery\ComponentDiscovery;
use Fabryq\Runtime\Discovery\ManifestDiscovery;
use Fabryq\Runtime\Doctrine\DoctrineDiscovery;
use Fabryq\Runtime\Registry\AppRegistryFactory;
use Fabryq\Runtime\Resources\ResourceRegistry;
use Fabryq\Runtime\Util\ComponentSlugger;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

/**
 * Loads runtime services and configures application discovery.
 */
final class FabryqRuntimeExtension extends Extension
{
    /**
     * {@inheritDoc}
     *
     * Side effects:
     * - Registers bundle service definitions.
     * - Prepends Doctrine, Twig, and translation configuration.
     *
     * @param array<int, array<string, mixed>> $configs Bundle configuration arrays.
     *
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.php');

        $container->registerAttributeForAutoconfiguration(
            FabryqProvider::class,
            static function (ChildDefinition $definition, FabryqProvider $attribute): void {
                $definition->addTag('fabryq.capability_provider', [
                    'capability' => $attribute->capability,
                    'contract' => $attribute->contract,
                    'priority' => $attribute->priority,
                ]);
            }
        );

        $projectDir = (string)$container->getParameter('kernel.project_dir');
        $slugger = new ComponentSlugger();
        $manifestDiscovery = new ManifestDiscovery();
        $componentDiscovery = new ComponentDiscovery($slugger);
        $appRegistryFactory = new AppRegistryFactory($manifestDiscovery, $componentDiscovery);
        $appRegistry = $appRegistryFactory->build($projectDir);

        $doctrineDiscovery = new DoctrineDiscovery($appRegistry, $projectDir);
        $entityMappings = $doctrineDiscovery->getEntityMappings();
        if ($entityMappings !== []) {
            $container->prependExtensionConfig(
                'doctrine',
                [
                'orm' => [
                    'mappings' => $entityMappings,
                ],
            ]
            );
        }

        $migrationPaths = $doctrineDiscovery->getMigrationPaths();
        if ($migrationPaths !== []) {
            $container->prependExtensionConfig(
                'doctrine_migrations',
                [
                'migrations_paths' => $migrationPaths,
            ]
            );
        }

        $resourceRegistry = new ResourceRegistry($appRegistry);
        $templatePaths = $resourceRegistry->getTemplatePaths();
        if ($templatePaths !== []) {
            $twigPaths = array_fill_keys($templatePaths, null);
            $container->prependExtensionConfig(
                'twig',
                [
                'paths' => $twigPaths,
            ]
            );
        }

        $translationPaths = $resourceRegistry->getTranslationPaths();
        if ($translationPaths !== []) {
            $container->prependExtensionConfig(
                'framework',
                [
                'translator' => [
                    'paths' => $translationPaths,
                ],
            ]
            );
        }
    }
}
