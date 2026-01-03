<?php

/**
 * Console command that exports the capability graph.
 *
 * @package Fabryq\Cli\Command
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Command;

use Fabryq\Cli\Error\CliExitCode;
use Fabryq\Cli\Analyzer\GraphBuilder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Builds and writes the capability graph to disk.
 */
#[AsCommand(
    name: 'fabryq:graph',
    description: 'Export fabryq capability graph.'
)]
final class GraphCommand extends AbstractFabryqCommand
{
    /**
     * @param GraphBuilder $graphBuilder Graph builder service.
     * @param Filesystem $filesystem Filesystem abstraction for writing outputs.
     * @param string $projectDir Absolute project directory.
     */
    public function __construct(
        /**
         * Graph builder service.
         *
         * @var GraphBuilder
         */
        private readonly GraphBuilder $graphBuilder,
        /**
         * Filesystem abstraction used for writing files.
         *
         * @var Filesystem
         */
        private readonly Filesystem $filesystem,
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
        $this
            ->addOption('json', null, InputOption::VALUE_NONE, 'Write JSON output to state/graph/latest.json.')
            ->addOption('mermaid', null, InputOption::VALUE_NONE, 'Include Mermaid graph in Markdown output.')
            ->setDescription('Export fabryq capability graph.');
        parent::configure();
    }

    /**
     * {@inheritDoc}
     *
     * Side effects:
     * - Writes JSON and Markdown files to disk.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $graph = $this->graphBuilder->build();
        $payload = [
            'apps' => $graph,
        ];

        $jsonPath = $this->projectDir.'/state/graph/latest.json';
        $mdPath = $this->projectDir.'/state/graph/latest.md';

        $this->filesystem->mkdir(dirname($mdPath));

        if ($input->getOption('json')) {
            $this->filesystem->mkdir(dirname($jsonPath));
            file_put_contents($jsonPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        file_put_contents($mdPath, $this->renderMarkdown($payload, (bool) $input->getOption('mermaid')));

        $hasMissing = false;
        $hasDegraded = false;

        foreach ($graph as $app) {
            foreach ($app['consumes'] as $consume) {
                if ($consume['winner'] === null) {
                    $hasMissing = true;
                }
                if (!empty($consume['degraded'])) {
                    $hasDegraded = true;
                }
            }
        }

        if ($hasMissing) {
            return CliExitCode::PROJECT_STATE_ERROR;
        }

        if ($hasDegraded) {
            return CliExitCode::PROJECT_STATE_ERROR;
        }

        return CliExitCode::SUCCESS;
    }

    /**
     * Render a Markdown document for the graph payload.
     *
     * @param array<string, mixed> $payload
     *
     * @return string Markdown document contents.
     */
    private function renderMarkdown(array $payload, bool $includeMermaid): string
    {
        $lines = [];
        $lines[] = '# Fabryq Graph';
        $lines[] = '';

        if ($payload['apps'] === []) {
            $lines[] = 'No apps discovered.';
            $lines[] = '';
            return implode("\n", $lines);
        }

        foreach ($payload['apps'] as $appId => $app) {
            $lines[] = '## '.$appId;
            $lines[] = '';
            $lines[] = 'Consumes:';
            if ($app['consumes'] === []) {
                $lines[] = '- none';
            } else {
                foreach ($app['consumes'] as $consume) {
                    $required = $consume['required'] ? 'required' : 'optional';
                    $degraded = !empty($consume['degraded']) ? ' degraded' : '';
                    $lines[] = sprintf('- %s (%s%s)', $consume['capabilityId'], $required, $degraded);
                    if (!empty($consume['contract'])) {
                        $lines[] = '  Contract: '.$consume['contract'];
                    }
                    if ($consume['winner'] !== null) {
                        $lines[] = '  Winner: '.$consume['winner']['className'];
                    } else {
                        $lines[] = '  Winner: missing';
                    }
                    if ($consume['providers'] !== []) {
                        $lines[] = '  Providers:';
                        foreach ($consume['providers'] as $provider) {
                            $winnerFlag = !empty($provider['winner']) ? ' (winner)' : '';
                            $lines[] = sprintf('    - %s (priority %d)%s', $provider['className'], (int) $provider['priority'], $winnerFlag);
                        }
                    }
                }
            }
            $lines[] = '';
        }

        if ($includeMermaid) {
            $lines[] = '## Mermaid';
            $lines[] = '';
            $lines[] = '```mermaid';
            $lines[] = 'graph TD';
            foreach ($payload['apps'] as $appId => $app) {
                foreach ($app['consumes'] as $consume) {
                    $capability = $consume['capabilityId'];
                    $winner = $consume['winner']['className'] ?? 'missing';
                    $lines[] = sprintf('  %s --> %s', $this->slug($appId), $this->slug($capability));
                    $lines[] = sprintf('  %s --> %s', $this->slug($capability), $this->slug($winner));
                }
            }
            $lines[] = '```';
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    /**
     * Normalize a string for Mermaid node ids.
     *
     * @param string $value Value to normalize.
     *
     * @return string Normalized node id.
     */
    private function slug(string $value): string
    {
        $slug = preg_replace('/[^A-Za-z0-9]+/', '_', $value) ?? $value;
        $slug = trim($slug, '_');
        if ($slug === '') {
            return 'node';
        }

        return $slug;
    }
}
