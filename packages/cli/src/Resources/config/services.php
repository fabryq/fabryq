<?php

/**
 * Service definitions for the Fabryq CLI bundle.
 *
 * @package Fabryq\Cli\Resources
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

use Fabryq\Cli\Analyzer\CrossAppReferenceScanner;
use Fabryq\Cli\Analyzer\Doctor;
use Fabryq\Cli\Analyzer\EntityBaseScanner;
use Fabryq\Cli\Analyzer\GraphBuilder;
use Fabryq\Cli\Analyzer\ServiceLocatorScanner;
use Fabryq\Cli\Analyzer\Verifier;
use Fabryq\Cli\Assets\AssetInstaller;
use Fabryq\Cli\Assets\AssetManifestWriter;
use Fabryq\Cli\Assets\AssetScanner;
use Fabryq\Cli\Command\AssetsInstallCommand;
use Fabryq\Cli\Command\AppCreateCommand;
use Fabryq\Cli\Command\ComponentCreateCommand;
use Fabryq\Cli\Command\DoctorCommand;
use Fabryq\Cli\Command\FixCommand;
use Fabryq\Cli\Command\FixAssetsCommand;
use Fabryq\Cli\Command\FixCrossingCommand;
use Fabryq\Cli\Command\GraphCommand;
use Fabryq\Cli\Command\ReviewCommand;
use Fabryq\Cli\Command\VerifyCommand;
use Fabryq\Cli\Fix\FixRunLogger;
use Fabryq\Cli\Gate\DoctrineGate;
use Fabryq\Cli\Report\FindingIdGenerator;
use Fabryq\Cli\Report\ReportWriter;
use Fabryq\Cli\Report\ReviewWriter;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Filesystem\Filesystem;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

/**
 * Register CLI analyzers, gates, and console commands.
 *
 * @param ContainerConfigurator $configurator Symfony DI configurator.
 *
 * @return void
 */
return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services->set(Filesystem::class);

    $services->set(FindingIdGenerator::class)
        ->args(['%kernel.project_dir%']);

    $services->set(ReportWriter::class)
        ->args([service(Filesystem::class), service(FindingIdGenerator::class)]);

    $services->set(ReviewWriter::class)
        ->args([service(Filesystem::class), service(FindingIdGenerator::class)]);

    $services->set(CrossAppReferenceScanner::class);
    $services->set(ServiceLocatorScanner::class);
    $services->set(EntityBaseScanner::class);
    $services->set(DoctrineGate::class);
    $services->set(Verifier::class);
    $services->set(Doctor::class)
        ->args([service('Fabryq\\Runtime\\Registry\\AppRegistry'), service('Fabryq\\Runtime\\Registry\\CapabilityProviderRegistry'), '%fabryq.capabilities.map%']);
    $services->set(GraphBuilder::class)
        ->args([service('Fabryq\\Runtime\\Registry\\AppRegistry'), '%fabryq.capabilities.map%']);

    $services->set(AssetScanner::class)
        ->args([service('Fabryq\\Runtime\\Registry\\AppRegistry'), service('Fabryq\\Runtime\\Util\\ComponentSlugger'), '%kernel.project_dir%']);

    $services->set(AssetInstaller::class)
        ->args([service(Filesystem::class), service(AssetScanner::class)]);

    $services->set(AssetManifestWriter::class)
        ->args([service(Filesystem::class), '%kernel.project_dir%']);

    $services->set(FixRunLogger::class)
        ->args([service(Filesystem::class), '%kernel.project_dir%']);

    $services->set(VerifyCommand::class)
        ->args([service(Verifier::class), service(ReportWriter::class), '%kernel.project_dir%']);

    $services->set(ReviewCommand::class)
        ->args([service(Verifier::class), service(ReviewWriter::class), '%kernel.project_dir%']);

    $services->set(DoctorCommand::class)
        ->args([service(Doctor::class), service(ReportWriter::class), '%kernel.project_dir%']);

    $services->set(GraphCommand::class)
        ->args([service(GraphBuilder::class), service(Filesystem::class), '%kernel.project_dir%']);

    $services->set(AssetsInstallCommand::class)
        ->args([service(AssetInstaller::class), service(AssetManifestWriter::class)]);

    $services->set(FixCommand::class)
        ->args([service(Verifier::class), service(FindingIdGenerator::class), '%kernel.project_dir%']);

    $services->set(FixAssetsCommand::class)
        ->args([service(AssetScanner::class), service(AssetManifestWriter::class), service(Filesystem::class), service(FixRunLogger::class), service(FindingIdGenerator::class)]);

    $services->set(FixCrossingCommand::class)
        ->args([service(Verifier::class), service('Fabryq\\Runtime\\Registry\\AppRegistry'), service(FixRunLogger::class), service(FindingIdGenerator::class), service(Filesystem::class), service('Fabryq\\Runtime\\Util\\ComponentSlugger'), '%kernel.project_dir%']);

    $services->set(AppCreateCommand::class)
        ->args([service(Filesystem::class), service('Fabryq\\Runtime\\Registry\\AppRegistry'), service('Fabryq\\Runtime\\Util\\ComponentSlugger'), '%kernel.project_dir%']);

    $services->set(ComponentCreateCommand::class)
        ->args([service(Filesystem::class), service('Fabryq\\Runtime\\Registry\\AppRegistry'), service('Fabryq\\Runtime\\Util\\ComponentSlugger'), '%kernel.project_dir%']);
};
