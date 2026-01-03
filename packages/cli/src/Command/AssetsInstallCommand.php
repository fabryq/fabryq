<?php

/**
 * Console command that publishes Fabryq assets.
 *
 * @package Fabryq\Cli\Command
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Command;

use Fabryq\Cli\Assets\AssetScanner;
use Fabryq\Cli\Error\CliExitCode;
use Fabryq\Cli\Lock\WriteLock;
use Fabryq\Cli\Assets\AssetInstaller;
use Fabryq\Cli\Assets\AssetManifestWriter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Installs assets and writes the asset manifest.
 */
#[AsCommand(
    name: 'fabryq:assets:install',
    description: 'Publish Fabryq assets to public/fabryq.'
)]
final class AssetsInstallCommand extends AbstractFabryqCommand
{
    /**
     * @param AssetInstaller      $assetInstaller Asset installer service.
     * @param AssetManifestWriter $manifestWriter Asset manifest writer service.
     * @param AssetScanner        $assetScanner   Asset scanner service.
     * @param WriteLock           $writeLock      Write lock guard.
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
        /**
         * Asset scanner service.
         *
         * @var AssetScanner
         */
        private readonly AssetScanner $assetScanner,
        /**
         * Write lock guard.
         *
         * @var WriteLock
         */
        private readonly WriteLock $writeLock,
    ) {
        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Plan changes without writing files.')
            ->setDescription('Publish Fabryq assets to public/fabryq.');
        parent::configure();
    }

    /**
     * {@inheritDoc}
     *
     * Side effects:
     * - Writes assets and manifest files to disk.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');

        if ($dryRun) {
            $scan = $this->assetScanner->scan();
            $io->title('Dry-run: fabryq:assets:install');
            if ($scan->entries === []) {
                $io->text('No assets discovered.');
            } else {
                $lines = [];
                foreach ($scan->entries as $entry) {
                    $lines[] = sprintf('%s -> %s', $entry['source'], $entry['target']);
                }
                $io->listing($lines);
            }

            if ($scan->collisions !== []) {
                $io->error('FABRYQ.PUBLIC.COLLISION: asset targets overlap.');
                foreach ($scan->collisions as $collision) {
                    $io->text(' - ' . $collision['target']);
                }
                return CliExitCode::PROJECT_STATE_ERROR;
            }

            $io->success('No files were written.');
            return CliExitCode::SUCCESS;
        }

        $this->writeLock->acquire();

        try {
            $result = $this->assetInstaller->install();
            $this->manifestWriter->write($result);
        } finally {
            $this->writeLock->release();
        }

        if ($result->collisions !== []) {
            $output->writeln('<error>FABRYQ.PUBLIC.COLLISION: asset targets overlap.</error>');
            foreach ($result->collisions as $collision) {
                $output->writeln(' - '.$collision['target']);
            }
            return CliExitCode::PROJECT_STATE_ERROR;
        }

        return CliExitCode::SUCCESS;
    }
}
