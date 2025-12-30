<?php

/**
 * Console command that runs verification gates.
 *
 * @package Fabryq\Cli\Command
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Command;

use Fabryq\Cli\Analyzer\Verifier;
use Fabryq\Cli\Report\ReportWriter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Runs the verifier and writes verification reports.
 */
final class VerifyCommand extends Command
{
    /**
     * Default command name registered with Symfony.
     *
     * @var string
     */
    protected static string $defaultName = 'verify';

    /**
     * @param Verifier $verifier Verification analyzer.
     * @param ReportWriter $reportWriter Report writer for JSON/Markdown output.
     * @param string $projectDir Absolute project directory.
     */
    public function __construct(
        /**
         * Verification analyzer.
         *
         * @var Verifier
         */
        private readonly Verifier $verifier,
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
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Run fabryq verification gates.');
    }

    /**
     * {@inheritDoc}
     *
     * Side effects:
     * - Writes report files to disk.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $findings = $this->verifier->verify($this->projectDir);

        $this->reportWriter->write(
            'verify',
            $findings,
            $this->projectDir.'/state/reports/verify/latest.json',
            $this->projectDir.'/state/reports/verify/latest.md'
        );

        $blockers = array_filter($findings, static fn ($finding) => $finding->severity === 'BLOCKER');

        return $blockers === [] ? Command::SUCCESS : Command::FAILURE;
    }
}
