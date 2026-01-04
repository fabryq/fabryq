<?php

/**
 * Console command that runs verification gates.
 *
 * @package   Fabryq\Cli\Command
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Command;

use Fabryq\Cli\Error\CliExitCode;
use Fabryq\Cli\Analyzer\Verifier;
use Fabryq\Cli\Report\ReportWriter;
use Fabryq\Cli\Report\Severity;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Runs the verifier and writes verification reports.
 */
#[AsCommand(
    name: 'fabryq:verify',
    description: 'Run fabryq verification gates.'
)]
final class VerifyCommand extends AbstractFabryqCommand
{
    /**
     * @param Verifier     $verifier     Verification analyzer.
     * @param ReportWriter $reportWriter Report writer for JSON/Markdown output.
     * @param string       $projectDir   Absolute project directory.
     */
    public function __construct(
        /**
         * Verification analyzer.
         *
         * @var Verifier
         */
        private readonly Verifier     $verifier,
        /**
         * Report writer used to persist findings.
         *
         * @var ReportWriter
         */
        private readonly ReportWriter $reportWriter,
        /**
         * Absolute project directory.
         *
         * @var string
         */
        private readonly string       $projectDir,
    ) {
        parent::__construct();
    }

    /**
     * {@inheritDoc}
     *
     * Side effects:
     * - Writes report files to disk.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Fabryq Verification');

        // 1. Run the verification.
        $findings = $this->verifier->verify($this->projectDir);

        // 2. Write the report to disk.
        $this->reportWriter->write(
            'verify',
            $findings,
            $this->projectDir . '/state/reports/verify/latest.json',
            $this->projectDir . '/state/reports/verify/latest.md'
        );

        // 3. Display results in the console (previously missing).
        if ($findings === []) {
            $io->success('No issues found.');
            return CliExitCode::SUCCESS;
        }

        foreach ($findings as $finding) {
            $type = $finding->severity === Severity::BLOCKER ? 'error' : 'warning';

            $io->section(sprintf('[%s] %s', $finding->ruleKey, $finding->severity));
            $io->text($finding->message);

            if ($finding->location) {
                $io->text(sprintf('File: %s', $finding->location->file));
                if ($finding->location->line) {
                    $io->text(sprintf('Line: %d', $finding->location->line));
                }
            }
        }

        $blockers = array_filter($findings, static fn ($finding) => $finding->severity === Severity::BLOCKER);

        if ($blockers !== []) {
            $io->error(sprintf('Found %d blockers.', count($blockers)));
            return CliExitCode::PROJECT_STATE_ERROR;
        }

        $io->success('Verification passed (with warnings).');
        return CliExitCode::SUCCESS;
    }
}
