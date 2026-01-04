<?php

/**
 * Review report writer for Markdown output.
 *
 * @package   Fabryq\Cli\Report
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Report;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Writes review reports grouped by rule key.
 */
final readonly class ReviewWriter
{
    /**
     * @param Filesystem         $filesystem  Filesystem abstraction for writing files.
     * @param FindingIdGenerator $idGenerator Finding ID generator.
     * @param ReportLinkBuilder  $linkBuilder Link builder for Markdown locations.
     */
    public function __construct(
        /**
         * Filesystem abstraction used for writing.
         *
         * @var Filesystem
         */
        private Filesystem         $filesystem,
        /**
         * Finding ID generator used for normalization.
         *
         * @var FindingIdGenerator
         */
        private FindingIdGenerator $idGenerator,
        /**
         * Markdown link builder.
         *
         * @var ReportLinkBuilder
         */
        private ReportLinkBuilder  $linkBuilder,
    ) {
    }

    /**
     * Write a review report to Markdown.
     *
     * @param Finding[] $findings Findings to include.
     * @param string    $mdPath   Absolute path to the Markdown report.
     */
    public function write(array $findings, string $mdPath): void
    {
        $blockers = [];
        $warnings = [];
        $groups = [];

        foreach ($findings as $finding) {
            $normalized = $this->normalizeFinding($finding);
            if ($normalized['severity'] === Severity::BLOCKER) {
                $blockers[] = $normalized;
            } else {
                $warnings[] = $normalized;
            }
            $groups[$normalized['ruleKey']][] = $normalized;
        }

        ksort($groups);

        $lines = [];
        $lines[] = '# Fabryq Review Report';
        $lines[] = '';
        $lines[] = '## Summary';
        $lines[] = '';
        $lines[] = sprintf('- Blockers: %d', count($blockers));
        $lines[] = sprintf('- Warnings: %d', count($warnings));
        $lines[] = '';

        $lines[] = '## Blockers';
        $lines[] = '';
        if ($blockers === []) {
            $lines[] = 'None.';
        } else {
            foreach ($blockers as $finding) {
                $lines[] = sprintf('- [%s] %s %s', $finding['id'], $finding['ruleKey'], $finding['severity']);
            }
        }
        $lines[] = '';

        $lines[] = '## Warnings';
        $lines[] = '';
        if ($warnings === []) {
            $lines[] = 'None.';
        } else {
            foreach ($warnings as $finding) {
                $lines[] = sprintf('- [%s] %s %s', $finding['id'], $finding['ruleKey'], $finding['severity']);
            }
        }
        $lines[] = '';

        $lines[] = '## Findings';
        $lines[] = '';
        if ($groups === []) {
            $lines[] = 'No findings.';
            $lines[] = '';
        } else {
            foreach ($groups as $ruleKey => $groupFindings) {
                $lines[] = '### ' . $ruleKey;
                $lines[] = '';
                foreach ($groupFindings as $finding) {
                    $lines[] = sprintf('- [%s] %s %s', $finding['id'], $finding['ruleKey'], $finding['severity']);
                    if ($finding['file'] !== null && $finding['file'] !== '') {
                        $lines[] = '  File: ' . $this->linkBuilder->format($finding['file'], $finding['line']);
                    }
                    if ($finding['symbol'] !== null && $finding['symbol'] !== '') {
                        $lines[] = '  Symbol: ' . $finding['symbol'];
                    }
                    $lines[] = '  Hint: ' . $finding['hint'];
                    if ($finding['autofix']['available'] && $finding['autofix']['fixer']) {
                        $lines[] = '  Autofix: fabryq fix ' . $finding['autofix']['fixer'] . ' --finding=' . $finding['id'];
                    }
                }
                $lines[] = '';
            }
        }

        $this->filesystem->mkdir(dirname($mdPath));
        file_put_contents($mdPath, implode("\n", $lines));
    }

    /**
     * Normalize a finding for review rendering.
     *
     * @param Finding $finding Finding to normalize.
     *
     * @return array<string, mixed>
     */
    private function normalizeFinding(Finding $finding): array
    {
        $location = $this->idGenerator->normalizeLocation($finding->location);
        $autofix = ['available' => $finding->autofixAvailable, 'fixer' => $finding->autofixFixer];

        return [
            'id' => $this->idGenerator->generate($finding),
            'ruleKey' => $finding->ruleKey,
            'severity' => $finding->severity,
            'message' => $finding->message,
            'hint' => $finding->hint ?? $finding->message,
            'file' => $location['file'] ?? null,
            'line' => $location['line'] ?? null,
            'symbol' => $location['symbol'] ?? null,
            'autofix' => $autofix,
        ];
    }
}
