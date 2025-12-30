<?php

/**
 * Console command that runs doctor checks.
 *
 * @package Fabryq\Cli\Command
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Command;

use Fabryq\Cli\Analyzer\Doctor;
use Fabryq\Cli\Report\ReportWriter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Evaluates provider wiring and writes doctor reports.
 */
final class DoctorCommand extends Command
{
    /**
     * Default command name registered with Symfony.
     *
     * @var string
     */
    protected static string $defaultName = 'doctor';

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

        $blockers = array_filter($result->findings, static fn ($finding) => $finding->severity === 'BLOCKER');

        return $blockers === [] ? Command::SUCCESS : Command::FAILURE;
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

        $lines[] = '| App | Status | Missing Required | Missing Optional |';
        $lines[] = '| --- | --- | --- | --- |';
        foreach ($appStatuses as $appId => $status) {
            $lines[] = sprintf(
                '| %s | %s | %s | %s |',
                $appId,
                $status['status'],
                implode(', ', $status['missingRequired']),
                implode(', ', $status['missingOptional'])
            );
        }
        $lines[] = '';

        return implode("\n", $lines);
    }
}
