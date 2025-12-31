<?php

/**
 * Fix run logger for planning and change tracking.
 *
 * @package Fabryq\Cli\Fix
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Fix;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Writes fix run plans, changes, and latest pointers.
 */
final readonly class FixRunLogger
{
    /**
     * @param Filesystem $filesystem Filesystem abstraction for writing files.
     * @param string     $projectDir Absolute project directory.
     */
    public function __construct(
        /**
         * Filesystem abstraction used for writing.
         *
         * @var Filesystem
         */
        private Filesystem $filesystem,
        /**
         * Absolute project directory.
         *
         * @var string
         */
        private string $projectDir,
    ) {}

    /**
     * Start a fix run and write the plan.
     *
     * @param string            $fixer       Fixer key.
     * @param string            $mode        Fix mode.
     * @param string            $planMarkdown Plan Markdown content.
     * @param FixSelection|null $selection   Selection payload for determinism.
     *
     * @return FixRunContext
     */
    public function start(string $fixer, string $mode, string $planMarkdown, ?FixSelection $selection = null): FixRunContext
    {
        $runId = $this->generateRunId($fixer, $mode, $planMarkdown, $selection);
        $runDir = $this->projectDir.'/state/fix/'.$runId;
        $planPath = $runDir.'/plan.md';

        if ($this->filesystem->exists($planPath)) {
            $existing = (string) file_get_contents($planPath);
            if ($existing !== $planMarkdown) {
                throw new \RuntimeException('Existing plan differs from current plan.');
            }
        } else {
            $this->filesystem->mkdir($runDir);
            file_put_contents($planPath, $planMarkdown);
        }

        return new FixRunContext($runId, $runDir, date('c'));
    }

    /**
     * Finalize a fix run with change logs and latest pointers.
     *
     * @param FixRunContext $context    Run context.
     * @param string        $fixer      Fixer key.
     * @param string        $mode       Fix mode.
     * @param string        $result     Result label.
     * @param array<int, string> $changedFiles Changed file paths.
     * @param int           $blockers   Blocker count.
     * @param int           $warnings   Warning count.
     */
    public function finish(
        FixRunContext $context,
        string $fixer,
        string $mode,
        string $result,
        array $changedFiles,
        int $blockers,
        int $warnings,
    ): void {
        $changesPath = $context->runDir.'/changes.json';
        $changes = [
            'changedFiles' => array_values(array_unique($changedFiles)),
        ];

        file_put_contents($changesPath, json_encode($changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $latestPayload = [
            'runId' => $context->runId,
            'startedAt' => $context->startedAt,
            'finishedAt' => date('c'),
            'mode' => $mode,
            'fixer' => $fixer,
            'result' => $result,
            'counts' => [
                'changedFiles' => count($changes['changedFiles']),
                'blockers' => $blockers,
                'warnings' => $warnings,
            ],
            'path' => 'state/fix/'.$context->runId,
        ];

        $latestJson = $this->projectDir.'/state/fix/latest.json';
        $latestMd = $this->projectDir.'/state/fix/latest.md';

        $this->filesystem->mkdir(dirname($latestJson));
        $this->filesystem->mkdir(dirname($latestMd));

        file_put_contents($latestJson, json_encode($latestPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        file_put_contents($latestMd, $this->renderLatestMarkdown($latestPayload));
    }

    /**
     * Render the latest Markdown summary.
     *
     * @param array<string, mixed> $payload Latest payload data.
     *
     * @return string Markdown content.
     */
    private function renderLatestMarkdown(array $payload): string
    {
        $lines = [];
        $lines[] = '# Fabryq Fix Run';
        $lines[] = '';
        $lines[] = 'RunId: '.$payload['runId'];
        $lines[] = 'Fixer: '.$payload['fixer'];
        $lines[] = 'Mode: '.$payload['mode'];
        $lines[] = 'Result: '.$payload['result'];
        $lines[] = 'Path: '.$payload['path'];
        $lines[] = '';
        $lines[] = sprintf('Changed Files: %d', $payload['counts']['changedFiles']);
        $lines[] = sprintf('Blockers: %d', $payload['counts']['blockers']);
        $lines[] = sprintf('Warnings: %d', $payload['counts']['warnings']);
        $lines[] = '';

        return implode("\n", $lines);
    }

    /**
     * Generate a deterministic run ID from inputs.
     *
     * @param string            $fixer       Fixer key.
     * @param string            $mode        Fix mode.
     * @param string            $planMarkdown Plan content.
     * @param FixSelection|null $selection   Selection payload.
     *
     * @return string Deterministic run ID.
     */
    private function generateRunId(string $fixer, string $mode, string $planMarkdown, ?FixSelection $selection): string
    {
        $payload = [
            'fixer' => $fixer,
            'mode' => $mode,
            'selection' => $selection?->toArray(),
            'plan' => $planMarkdown,
        ];

        $hash = sha1(json_encode($payload, JSON_UNESCAPED_SLASHES));

        return substr($hash, 0, 12);
    }
}
