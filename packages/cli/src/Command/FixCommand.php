<?php

/**
 * Console command that dispatches fixers.
 *
 * @package Fabryq\Cli\Command
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Command;

use Fabryq\Cli\Analyzer\Verifier;
use Fabryq\Cli\Fix\FixMode;
use Fabryq\Cli\Fix\FixSelection;
use Fabryq\Cli\Report\Finding;
use Fabryq\Cli\Report\FindingIdGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Dispatches fixers based on available autofix findings.
 */
#[AsCommand(
    name: 'fabryq:fix',
    description: 'Dispatch fabryq fixers based on autofixable findings.'
)]
final class FixCommand extends Command
{
    /**
     * @param Verifier           $verifier    Verification analyzer.
     * @param FindingIdGenerator $idGenerator Finding ID generator.
     * @param string             $projectDir  Absolute project directory.
     */
    public function __construct(
        /**
         * Verification analyzer.
         *
         * @var Verifier
         */
        private readonly Verifier           $verifier,
        /**
         * Finding ID generator.
         *
         * @var FindingIdGenerator
         */
        private readonly FindingIdGenerator $idGenerator,
        /**
         * Absolute project directory.
         *
         * @var string
         */
        private readonly string             $projectDir,
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
            ->addOption('all', null, InputOption::VALUE_NONE, 'Select all autofixable findings.')
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'Filter by file path.')
            ->addOption('symbol', null, InputOption::VALUE_REQUIRED, 'Filter by symbol.')
            ->addOption('finding', null, InputOption::VALUE_REQUIRED, 'Filter by finding id.')
            ->setDescription('Dispatch fabryq fixers based on autofixable findings.');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $mode = $this->resolveMode($input, $io);
        if ($mode === null) {
            return Command::FAILURE;
        }

        try {
            $selection = FixSelection::fromInput($input);
        } catch (\InvalidArgumentException $exception) {
            $io->error($exception->getMessage());
            return Command::FAILURE;
        }

        $findings = $this->verifier->verify($this->projectDir);
        $fixable = array_values(array_filter($findings, static fn (Finding $finding) => $finding->autofixAvailable));

        $selected = array_values(array_filter(
            $fixable,
            fn (Finding $finding) => $selection->matchesFinding($finding, $this->idGenerator)
        ));

        if ($selection->findingId !== null) {
            if (count($selected) !== 1) {
                $io->error('Finding selection did not resolve to exactly one autofixable finding.');
                return Command::FAILURE;
            }
        }

        $groups = [];
        foreach ($selected as $finding) {
            if ($finding->autofixFixer === null) {
                continue;
            }
            $groups[$finding->autofixFixer][] = $finding;
        }

        if ($groups === []) {
            $io->success('No autofixable findings matched.');
            return Command::SUCCESS;
        }

        $application = $this->getApplication();
        if ($application === null) {
            $io->error('Unable to locate application for dispatch.');
            return Command::FAILURE;
        }

        $exitCode = Command::SUCCESS;
        foreach (array_keys($groups) as $fixer) {
            $commandName = $this->resolveFixerCommand($fixer);
            if ($commandName === null) {
                $io->error(sprintf('Unknown fixer "%s".', $fixer));
                $exitCode = Command::FAILURE;
                continue;
            }

            $command = $application->find($commandName);
            $arguments = $this->buildArguments($commandName, $mode, $selection);
            $arrayInput = new ArrayInput($arguments);
            $arrayInput->setInteractive(false);

            $exitCode = max($exitCode, $command->run($arrayInput, $output));
        }

        return $exitCode;
    }

    /**
     * Resolve the fix mode from input.
     *
     * @param InputInterface $input Console input.
     * @param SymfonyStyle   $io    Console style helper.
     *
     * @return string|null Fix mode or null on error.
     */
    private function resolveMode(InputInterface $input, SymfonyStyle $io): ?string
    {
        $dryRun = (bool) $input->getOption('dry-run');
        $apply = (bool) $input->getOption('apply');

        if ($dryRun === $apply) {
            $io->error('Specify exactly one of --dry-run or --apply.');
            return null;
        }

        return $dryRun ? FixMode::DRY_RUN : FixMode::APPLY;
    }

    /**
     * Build command arguments for a fixer.
     *
     * @param string       $commandName Command name.
     * @param string       $mode        Fix mode.
     * @param FixSelection $selection   Selection payload.
     *
     * @return array<string, mixed>
     */
    private function buildArguments(string $commandName, string $mode, FixSelection $selection): array
    {
        $arguments = ['command' => $commandName];

        if ($mode === FixMode::DRY_RUN) {
            $arguments['--dry-run'] = true;
        } else {
            $arguments['--apply'] = true;
        }

        if ($selection->all) {
            $arguments['--all'] = true;
        } elseif ($selection->file !== null) {
            $arguments['--file'] = $selection->file;
        } elseif ($selection->symbol !== null) {
            $arguments['--symbol'] = $selection->symbol;
        } elseif ($selection->findingId !== null) {
            $arguments['--finding'] = $selection->findingId;
        }

        return $arguments;
    }

    /**
     * Resolve the command name for a fixer key.
     *
     * @param string $fixer Fixer key.
     *
     * @return string|null Command name.
     */
    private function resolveFixerCommand(string $fixer): ?string
    {
        return match ($fixer) {
            'assets' => 'fabryq:fix:assets',
            'crossing' => 'fabryq:fix:crossing',
            default => null,
        };
    }
}
