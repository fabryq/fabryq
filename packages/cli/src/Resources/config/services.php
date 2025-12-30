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
use Fabryq\Cli\Analyzer\GraphBuilder;
use Fabryq\Cli\Analyzer\Verifier;
use Fabryq\Cli\Assets\AssetInstaller;
use Fabryq\Cli\Assets\AssetManifestWriter;
use Fabryq\Cli\Assets\AssetScanner;
use Fabryq\Cli\Command\AssetsInstallCommand;
use Fabryq\Cli\Command\DoctorCommand;
use Fabryq\Cli\Command\GraphCommand;
use Fabryq\Cli\Command\ReviewCommand;
use Fabryq\Cli\Command\VerifyCommand;
use Fabryq\Cli\Gate\DoctrineGate;
use Fabryq\Cli\Report\ReportWriter;
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

    $services->set(ReportWriter::class);

    $services->set(CrossAppReferenceScanner::class);
    $services->set(DoctrineGate::class);
    $services->set(Verifier::class);
    $services->set(Doctor::class);
    $services->set(GraphBuilder::class);

    $services->set(AssetScanner::class)
        ->args([service('Fabryq\\Runtime\\Registry\\AppRegistry'), service('Fabryq\\Runtime\\Util\\ComponentSlugger'), '%kernel.project_dir%']);

    $services->set(AssetInstaller::class)
        ->args([service(Filesystem::class), service(AssetScanner::class)]);

    $services->set(AssetManifestWriter::class)
        ->args([service(Filesystem::class), '%kernel.project_dir%']);

    $services->set(VerifyCommand::class)
        ->args([service(Verifier::class), service(ReportWriter::class), '%kernel.project_dir%']);

    $services->set(ReviewCommand::class)
        ->args([service(Verifier::class), service(ReportWriter::class), '%kernel.project_dir%']);

    $services->set(DoctorCommand::class)
        ->args([service(Doctor::class), service(ReportWriter::class), '%kernel.project_dir%']);

    $services->set(GraphCommand::class)
        ->args([service(GraphBuilder::class), service(Filesystem::class), '%kernel.project_dir%']);

    $services->set(AssetsInstallCommand::class)
        ->args([service(AssetInstaller::class), service(AssetManifestWriter::class)]);
};
