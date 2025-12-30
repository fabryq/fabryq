<?php

/**
 * Console command that publishes Fabryq assets.
 *
 * @package Fabryq\Cli\Command
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Command;

use Fabryq\Cli\Assets\AssetInstaller;
use Fabryq\Cli\Assets\AssetManifestWriter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Installs assets and writes the asset manifest.
 */
#[AsCommand(
    name: 'fabryq:assets:install',
    description: 'Publish Fabryq assets to public/fabryq.'
)]
final class AssetsInstallCommand extends Command
{
    /**
     * @param AssetInstaller $assetInstaller Asset installer service.
     * @param AssetManifestWriter $manifestWriter Asset manifest writer service.
     */
    public function __construct(
        /**
         * Asset installer service.
         *
         * @var AssetInstaller
         */
        private readonly AssetInstaller $assetInstaller,
        /**
         * Asset manifest writer service.
         *
         * @var AssetManifestWriter
         */
        private readonly AssetManifestWriter $manifestWriter,
    ) {
        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Publish Fabryq assets to public/fabryq.');
    }

    /**
     * {@inheritDoc}
     *
     * Side effects:
     * - Writes assets and manifest files to disk.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = $this->assetInstaller->install();
        $this->manifestWriter->write($result);

        if ($result->collisions !== []) {
            $output->writeln('<error>FABRYQ.PUBLIC.COLLISION: asset targets overlap.</error>');
            foreach ($result->collisions as $collision) {
                $output->writeln(' - '.$collision['target']);
            }
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
