<?php

/**
 * Console command that dispatches fixers.
 *
 * @package Fabryq\Cli\Command
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Command;

use Fabryq\Cli\Error\CliExitCode;
use Fabryq\Cli\Error\InternalError;
use Fabryq\Cli\Error\UserError;
use Fabryq\Cli\Analyzer\Verifier;
use Fabryq\Cli\Fix\FixMode;
use Fabryq\Cli\Fix\FixSelection;
use Fabryq\Cli\Report\Finding;
use Fabryq\Cli\Report\FindingIdGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
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
final class FixCommand extends AbstractFabryqCommand
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

        $findings = $this->verifier->verify($this->projectDir);
        $fixable = array_values(array_filter($findings, static fn (Finding $finding) => $finding->autofixAvailable));

        $selected = array_values(array_filter(
            $fixable,
            fn (Finding $finding) => $selection->matchesFinding($finding, $this->idGenerator)
        ));

        if ($selection->findingId !== null) {
            if (count($selected) !== 1) {
                throw new UserError('Finding selection did not resolve to exactly one autofixable finding.');
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
            return CliExitCode::SUCCESS;
        }

        $application = $this->getApplication();
        if ($application === null) {
            throw new InternalError('Unable to locate application for dispatch.');
        }

        $exitCode = CliExitCode::SUCCESS;
        foreach (array_keys($groups) as $fixer) {
            $commandName = $this->resolveFixerCommand($fixer);
            if ($commandName === null) {
                $io->error(sprintf('Unknown fixer "%s".', $fixer));
                $exitCode = CliExitCode::INTERNAL_ERROR;
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
