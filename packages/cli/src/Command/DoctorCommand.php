<?php

/**
 * Console command that runs doctor checks.
 *
 * @package Fabryq\Cli\Command
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Command;

use Fabryq\Cli\Error\CliExitCode;
use Fabryq\Cli\Analyzer\Doctor;
use Fabryq\Cli\Report\ReportWriter;
use Fabryq\Cli\Report\Severity;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Evaluates provider wiring and writes doctor reports.
 */
#[AsCommand(
    name: 'fabryq:doctor',
    description: 'Run fabryq doctor checks.'
)]
final class DoctorCommand extends AbstractFabryqCommand
{
    /**
     * @param Doctor $doctor Doctor analyzer.
     * @param ReportWriter $reportWriter Report writer for JSON/Markdown output.
     * @param string $projectDir Absolute project directory.
     */
    public function __construct(
        /**
         * Doctor analyzer.
         *
         * @var Doctor
         */
        private readonly Doctor $doctor,
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
        $this->setDescription('Check fabryq provider wiring.');
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
        $result = $this->doctor->run();

        $markdownAppendix = $this->renderAppStatusTable($result->appStatuses);

        $this->reportWriter->write(
            'doctor',
            $result->findings,
            $this->projectDir.'/state/reports/doctor/latest.json',
            $this->projectDir.'/state/reports/doctor/latest.md',
            ['apps' => $result->appStatuses],
            $markdownAppendix
        );

        $hasBlockers = array_filter($result->findings, static fn ($finding) => $finding->severity === Severity::BLOCKER);
        $hasUnhealthy = array_filter(
            $result->appStatuses,
            static fn (array $status) => $status['status'] === 'UNHEALTHY'
        );
        $hasDegraded = array_filter(
            $result->appStatuses,
            static fn (array $status) => $status['status'] === 'DEGRADED'
        );

        if ($hasBlockers !== [] || $hasUnhealthy !== []) {
            return CliExitCode::PROJECT_STATE_ERROR;
        }

        if ($hasDegraded !== []) {
            return CliExitCode::PROJECT_STATE_ERROR;
        }

        return CliExitCode::SUCCESS;
    }

    /**
     * Render a Markdown table summarizing app statuses.
     *
     * @param array<string, array<string, mixed>> $appStatuses
     *
     * @return string Markdown table output.
     */
    private function renderAppStatusTable(array $appStatuses): string
    {
        $lines = [];
        $lines[] = '## Apps';
        $lines[] = '';
        if ($appStatuses === []) {
            $lines[] = 'No apps discovered.';
            $lines[] = '';
            return implode("\n", $lines);
        }

        $lines[] = '| App | Status | Missing Required | Missing Optional | Degraded |';
        $lines[] = '| --- | --- | --- | --- | --- |';
        foreach ($appStatuses as $appId => $status) {
            $lines[] = sprintf(
                '| %s | %s | %s | %s | %s |',
                $appId,
                $status['status'],
                implode(', ', $status['missingRequired']),
                implode(', ', $status['missingOptional']),
                implode(', ', $status['degraded'] ?? [])
            );
        }
        $lines[] = '';

        return implode("\n", $lines);
    }
}
