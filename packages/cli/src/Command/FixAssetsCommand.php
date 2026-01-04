<?php

/**
 * Console command that applies asset fixes.
 *
 * @package Fabryq\Cli\Command
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Command;

use Fabryq\Cli\Assets\AssetInstallResult;
use Fabryq\Cli\Assets\AssetManifestWriter;
use Fabryq\Cli\Assets\AssetScanner;
use Fabryq\Cli\Error\CliExitCode;
use Fabryq\Cli\Error\ProjectStateError;
use Fabryq\Cli\Error\UserError;
use Fabryq\Cli\Fix\FixMode;
use Fabryq\Cli\Fix\FixRunLogger;
use Fabryq\Cli\Fix\FixSelection;
use Fabryq\Cli\Lock\WriteLock;
use Fabryq\Cli\Report\Finding;
use Fabryq\Cli\Report\FindingIdGenerator;
use Fabryq\Cli\Report\FindingLocation;
use Fabryq\Cli\Report\Severity;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Publishes asset directories with dry-run/apply support.
 */
#[AsCommand(
    name: 'fabryq:fix:assets',
    description: 'Fix asset publishing and collisions.'
)]
final class FixAssetsCommand extends AbstractFabryqCommand
{
    /**
     * @param AssetScanner        $scanner        Asset scanner.
     * @param AssetManifestWriter $manifestWriter Asset manifest writer.
     * @param Filesystem          $filesystem     Filesystem abstraction.
     * @param FixRunLogger        $runLogger      Fix run logger.
     * @param FindingIdGenerator  $idGenerator    Finding ID generator.
     */
    public function __construct(
        /**
         * Asset scanner service.
         *
         * @var AssetScanner
         */
        private readonly AssetScanner        $scanner,
        /**
         * Asset manifest writer service.
         *
         * @var AssetManifestWriter
         */
        private readonly AssetManifestWriter $manifestWriter,
        /**
         * Filesystem abstraction used for publishing.
         *
         * @var Filesystem
         */
        private readonly Filesystem          $filesystem,
        /**
         * Fix run logger service.
         *
         * @var FixRunLogger
         */
        private readonly FixRunLogger        $runLogger,
        /**
         * Finding ID generator.
         *
         * @var FindingIdGenerator
         */
        private readonly FindingIdGenerator  $idGenerator,
        /**
         * Write lock guard.
         *
         * @var WriteLock
         */
        private readonly WriteLock           $writeLock,
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
            ->addOption('apply', null, InputOption::VALUE_NONE, 'Apply changes to disk.')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Select all assets.')
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'Filter by target path.')
            ->addOption('symbol', null, InputOption::VALUE_REQUIRED, 'Filter by symbol (not supported).')
            ->addOption('finding', null, InputOption::VALUE_REQUIRED, 'Filter by finding id.')
            ->setDescription('Fix asset publishing and collisions.');
        parent::configure();
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $mode = $this->resolveMode($input);

        try {
            $selection = FixSelection::fromInput($input);
        } catch (\InvalidArgumentException $exception) {
            throw new UserError($exception->getMessage(), previous: $exception);
        }

        if ($selection->symbol !== null) {
            throw new UserError('Symbol selection is not supported for asset fixes.');
        }

        $scanResult = $this->scanner->scan();
        $entries = [];
        foreach ($scanResult->entries as $entry) {
            $entry['method'] = 'pending';
            $entries[] = $entry;
        }

        $selectedEntries = [];
        if ($selection->findingId === null) {
            foreach ($entries as $entry) {
                if ($selection->matchesPath($entry['target'], $this->idGenerator)) {
                    $selectedEntries[] = $entry;
                }
            }
        }

        $selectedCollisions = [];
        foreach ($scanResult->collisions as $collision) {
            $finding = $this->buildCollisionFinding($collision);
            if ($selection->matchesFinding($finding, $this->idGenerator)) {
                $selectedCollisions[] = $collision;
            }
        }

        $blockers = 0;
        $warnings = 0;
        $planMarkdown = $this->renderPlan($mode, $selection, $selectedEntries, $selectedCollisions, $blockers, $warnings);

        try {
            $context = $this->runLogger->start('assets', $mode, $planMarkdown, $selection);
        } catch (\RuntimeException $exception) {
            throw new ProjectStateError($exception->getMessage(), previous: $exception);
        }

        if ($blockers > 0) {
            $this->runLogger->finish($context, 'assets', $mode, 'blocked', [], $blockers, $warnings);
            $io->error('Asset fix blocked.');
            return CliExitCode::PROJECT_STATE_ERROR;
        }

        if ($mode === FixMode::DRY_RUN) {
            $this->runLogger->finish($context, 'assets', $mode, 'ok', [], $blockers, $warnings);
            $io->success('Asset fix plan written (dry-run).');
            return CliExitCode::SUCCESS;
        }

        $this->writeLock->acquire();

        $changedFiles = [];
        $appliedEntries = [];
        try {
            foreach ($entries as $entry) {
                if (!$selection->matchesPath($entry['target'], $this->idGenerator)) {
                    $entry['method'] = 'skipped';
                    $appliedEntries[] = $entry;
                    continue;
                }

                $entry['method'] = $this->publish($entry['source'], $entry['target']);
                $changedFiles[] = $this->idGenerator->normalizePath($entry['target']);
                $appliedEntries[] = $entry;
            }

            $this->manifestWriter->write(new AssetInstallResult($appliedEntries, $scanResult->collisions));
            $this->runLogger->finish($context, 'assets', $mode, 'ok', $changedFiles, $blockers, $warnings);
        } finally {
            $this->writeLock->release();
        }

        $io->success('Assets published.');

        return CliExitCode::SUCCESS;
    }

    /**
     * Resolve the fix mode from input.
     *
     * @param InputInterface $input Console input.
     *
     * @return string Fix mode.
     */
    private function resolveMode(InputInterface $input): string
    {
        $dryRun = (bool) $input->getOption('dry-run');
        $apply = (bool) $input->getOption('apply');

        if ($dryRun === $apply) {
            throw new UserError('Specify exactly one of --dry-run or --apply.');
        }

        return $dryRun ? FixMode::DRY_RUN : FixMode::APPLY;
    }

    /**
     * Render the plan Markdown output.
     *
     * @param string       $mode              Fix mode.
     * @param FixSelection $selection         Selection criteria.
     * @param array        $entries           Selected entries.
     * @param array        $collisions        Selected collisions.
     * @param int          $blockers          Blocker count output.
     * @param int          $warnings          Warning count output.
     *
     * @return string Plan Markdown.
     */
    private function renderPlan(
        string $mode,
        FixSelection $selection,
        array $entries,
        array $collisions,
        int &$blockers,
        int &$warnings,
    ): string {
        $lines = [];
        $lines[] = '# Fabryq Fix Plan';
        $lines[] = '';
        $lines[] = 'Fixer: assets';
        $lines[] = 'Mode: '.$mode;
        $lines[] = 'Selection: '.json_encode($selection->toArray(), JSON_UNESCAPED_SLASHES);
        $lines[] = '';
        $lines[] = '## Targets';
        $lines[] = '';

        if ($entries === []) {
            $lines[] = 'No asset entries selected.';
            $lines[] = '';
            $warnings++;
        } else {
            foreach ($entries as $entry) {
                $target = $this->idGenerator->normalizePath($entry['target']);
                $source = $this->idGenerator->normalizePath($entry['source']);
                $lines[] = sprintf('- [FIX] %s <= %s', $target, $source);
            }
            $lines[] = '';
        }

        $lines[] = '## Blockers';
        $lines[] = '';
        if ($collisions === []) {
            $lines[] = 'None.';
        } else {
            foreach ($collisions as $collision) {
                $blockers++;
                $target = $this->idGenerator->normalizePath((string) $collision['target']);
                $sources = array_map(
                    fn (string $source) => $this->idGenerator->normalizePath($source),
                    $collision['sources']
                );
                $lines[] = sprintf('- [%s] %s (sources: %s)', Severity::BLOCKER, $target, implode(', ', $sources));
            }
        }
        $lines[] = '';

        return implode("\n", $lines);
    }

    /**
     * Build a collision finding for selection matching.
     *
     * @param array<string, mixed> $collision Collision data.
     *
     * @return Finding Finding object.
     */
    private function buildCollisionFinding(array $collision): Finding
    {
        $target = $this->idGenerator->normalizePath((string) $collision['target']);
        $sources = array_map(
            fn (string $source) => $this->idGenerator->normalizePath($source),
            $collision['sources']
        );

        return new Finding(
            'FABRYQ.PUBLIC.COLLISION',
            Severity::BLOCKER,
            sprintf('Asset target "%s" has multiple sources: %s', $target, implode(', ', $sources)),
            new FindingLocation($target, null, implode(', ', $sources)),
            ['primary' => $target]
        );
    }

    /**
     * Publish assets by symlink or copy.
     *
     * @param string $source Source path.
     * @param string $target Target path.
     *
     * @return string Publication method.
     */
    private function publish(string $source, string $target): string
    {
        if ($this->filesystem->exists($target)) {
            $this->filesystem->remove($target);
        }

        $this->filesystem->mkdir(dirname($target));

        try {
            $this->filesystem->symlink($source, $target);
            return 'symlink';
        } catch (\Throwable $exception) {
            if (is_dir($source)) {
                $this->filesystem->mirror($source, $target);
            } else {
                $this->filesystem->copy($source, $target);
            }
            return 'copy';
        }
    }
}
