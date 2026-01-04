<?php

/**
 * Console command that runs verification and writes review reports.
 *
 * @package Fabryq\Cli\Command
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Command;

use Fabryq\Cli\Error\CliExitCode;
use Fabryq\Cli\Analyzer\Verifier;
use Fabryq\Cli\Report\ReviewWriter;
use Fabryq\Cli\Report\Severity;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Runs verification and produces review output artifacts.
 */
#[AsCommand(
    name: 'fabryq:review',
    description: 'Run fabryq verification and generate a review report.'
)]
final class ReviewCommand extends AbstractFabryqCommand
{
    /**
 * @param Verifier $verifier Verification analyzer.
 * @param ReviewWriter $reviewWriter Review writer for Markdown output.
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
         * Review writer used to persist findings.
         *
         * @var ReviewWriter
         */
        private readonly ReviewWriter $reviewWriter,
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
        $this->setDescription('Run fabryq verification and generate a review report.');
        parent::configure();
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

        $this->reviewWriter->write(
            $findings,
            $this->projectDir.'/state/reports/review/latest.md'
        );

        $blockers = array_filter($findings, static fn ($finding) => $finding->severity === Severity::BLOCKER);

        return $blockers === [] ? CliExitCode::SUCCESS : CliExitCode::PROJECT_STATE_ERROR;
    }
}
