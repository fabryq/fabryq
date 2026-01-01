<?php

/**
 * Report writer for JSON and Markdown outputs.
 *
 * @package   Fabryq\Cli\Report
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Report;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Writes findings to disk in multiple formats.
 */
final readonly class ReportWriter
{
    /**
     * @param Filesystem         $filesystem   Filesystem abstraction for writing reports.
     * @param FindingIdGenerator $idGenerator  Finding ID generator.
     * @param string             $version      [Optional] Report schema version identifier.
     */
    public function __construct(
        /**
         * Filesystem abstraction used for directory creation.
         *
         * @var Filesystem
         */
        private Filesystem         $filesystem,
        /**
         * Finding ID generator and normalizer.
         *
         * @var FindingIdGenerator
         */
        private FindingIdGenerator $idGenerator,
        /**
         * Report schema version.
         *
         * @var string
         */
        private string             $version = '0.3'
    ) {}

    /**
     * Write report data to JSON and Markdown files.
     *
     * Side effects:
     * - Writes files to disk and creates directories as needed.
     *
     * @param string               $tool             Tool name used in the report header.
     * @param Finding[]            $findings         Findings to include in the report.
     * @param string               $jsonPath         Absolute path to the JSON report file.
     * @param string               $mdPath           Absolute path to the Markdown report file.
     * @param array<string, mixed> $extra            [Optional] Additional payload data.
     * @param string|null          $markdownAppendix [Optional] Extra Markdown appended to the report.
     */
    public function write(string $tool, array $findings, string $jsonPath, string $mdPath, array $extra = [], ?string $markdownAppendix = null): void
    {
        $blockers = 0;
        $warnings = 0;
        foreach ($findings as $finding) {
            if ($finding->severity === 'BLOCKER') {
                $blockers++;
            } elseif ($finding->severity === 'WARNING') {
                $warnings++;
            }
        }

        $result = $blockers > 0 ? 'blocked' : 'ok';
        $payload = array_merge(
            [
                'header' => [
                    'tool' => $tool,
                    'version' => $this->version,
                    'generatedAt' => date('c'),
                    'result' => $result,
                    'summary' => [
                        'blockers' => $blockers,
                        'warnings' => $warnings,
                    ],
                ],
                'findings' => array_map(fn(Finding $finding) => $finding->toArray($this->idGenerator), $findings),
            ], $extra
        );

        $this->filesystem->mkdir(dirname($jsonPath));
        $this->filesystem->mkdir(dirname($mdPath));

        file_put_contents($jsonPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        file_put_contents($mdPath, $this->renderMarkdown($tool, $payload, $markdownAppendix));
    }

    /**
     * Render a Markdown report from the payload data.
     *
     * @param string               $tool             Tool name displayed in the report.
     * @param array<string, mixed> $payload          Normalized report payload.
     * @param string|null          $markdownAppendix [Optional] Additional Markdown appended to the report.
     *
     * @return string Markdown document contents.
     */
    private function renderMarkdown(string $tool, array $payload, ?string $markdownAppendix): string
    {
        $header = $payload['header'];
        $lines = [];
        $lines[] = '# Fabryq ' . $tool . ' Report';
        $lines[] = '';
        $lines[] = 'Generated: ' . $header['generatedAt'];
        $lines[] = 'Result: ' . $header['result'];
        $lines[] = sprintf('Summary: %d blockers, %d warnings', $header['summary']['blockers'], $header['summary']['warnings']);
        $lines[] = '';
        $lines[] = '## Findings';

        if (count($payload['findings']) === 0) {
            $lines[] = '';
            $lines[] = 'No findings.';
            $lines[] = '';
            if ($markdownAppendix !== null) {
                $lines[] = $markdownAppendix;
            }
            return implode("\n", $lines);
        }

        $lines[] = '';
        $lines[] = '| Id | Severity | Rule | Message | Location |';
        $lines[] = '| --- | --- | --- | --- | --- |';

        foreach ($payload['findings'] as $finding) {
            $location = $finding['location']['file'] ?? '';
            if (!empty($finding['location']['line'])) {
                $location .= ':' . $finding['location']['line'];
            }
            $lines[] = sprintf(
                '| %s | %s | %s | %s | %s |',
                $finding['id'] ?? '',
                $finding['severity'],
                $finding['ruleKey'],
                str_replace("|", "\\|", (string)$finding['message']),
                $location
            );
        }

        $lines[] = '';

        if ($markdownAppendix !== null) {
            $lines[] = $markdownAppendix;
        }

        return implode("\n", $lines);
    }
}
