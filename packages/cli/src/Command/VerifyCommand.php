<?php

/**
 * Console command that runs verification gates.
 *
 * @package   Fabryq\Cli\Command
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Command;

use Fabryq\Cli\Analyzer\Verifier;
use Fabryq\Cli\Report\ReportWriter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
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
final class VerifyCommand extends Command
{
    /**
     * @param Verifier     $verifier     Verification analyzer.
     * @param ReportWriter $reportWriter Report writer for JSON/Markdown output.
     * @param string       $projectDir   Absolute project directory.
     */
    public function __construct(
        private readonly Verifier     $verifier,
        private readonly ReportWriter $reportWriter,
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

        // 1. Verifizierung ausführen
        $findings = $this->verifier->verify($this->projectDir);

        // 2. Bericht auf Festplatte schreiben
        $this->reportWriter->write(
            'verify',
            $findings,
            $this->projectDir . '/state/reports/verify/latest.json',
            $this->projectDir . '/state/reports/verify/latest.md'
        );

        // 3. Ergebnisse auf dem Bildschirm ausgeben (Das fehlte vorher)
        if ($findings === []) {
            $io->success('No issues found.');
            return Command::SUCCESS;
        }

        foreach ($findings as $finding) {
            $type = $finding->severity === 'BLOCKER' ? 'error' : 'warning';

            $io->section(sprintf('[%s] %s', $finding->type, $finding->severity));
            $io->text($finding->message);

            if ($finding->location) {
                $io->text(sprintf('File: %s', $finding->location->file));
                if ($finding->location->line) {
                    $io->text(sprintf('Line: %d', $finding->location->line));
                }
            }
        }

        $blockers = array_filter($findings, static fn($finding) => $finding->severity === 'BLOCKER');

        if ($blockers !== []) {
            $io->error(sprintf('Found %d blockers.', count($blockers)));
            return Command::FAILURE;
        }

        $io->success('Verification passed (with warnings).');
        return Command::SUCCESS;
    }
}