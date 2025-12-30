<?php

/**
 * Console command that exports the capability graph.
 *
 * @package Fabryq\Cli\Command
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Command;

use Fabryq\Cli\Analyzer\GraphBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Builds and writes the capability graph to disk.
 */
final class GraphCommand extends Command
{
    /**
     * Default command name registered with Symfony.
     *
     * @var string
     */
    protected static string $defaultName = 'graph';

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
        $this->setDescription('Export fabryq capability graph.');
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
            'generatedAt' => date('c'),
            'apps' => $graph,
        ];

        $jsonPath = $this->projectDir.'/state/graph/latest.json';
        $mdPath = $this->projectDir.'/state/graph/latest.md';

        $this->filesystem->mkdir(dirname($jsonPath));
        $this->filesystem->mkdir(dirname($mdPath));

        file_put_contents($jsonPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        file_put_contents($mdPath, $this->renderMarkdown($payload));

        return Command::SUCCESS;
    }

    /**
     * Render a Markdown document for the graph payload.
     *
     * @param array<string, mixed> $payload
     *
     * @return string Markdown document contents.
     */
    private function renderMarkdown(array $payload): string
    {
        $lines = [];
        $lines[] = '# Fabryq Graph';
        $lines[] = '';
        $lines[] = 'Generated: '.$payload['generatedAt'];
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
                    $lines[] = sprintf('- %s (%s)', $consume['capabilityId'], $required);
                }
            }
            $lines[] = '';
            $lines[] = 'Provides:';
            if ($app['provides'] === []) {
                $lines[] = '- none';
            } else {
                foreach ($app['provides'] as $provide) {
                    $lines[] = sprintf('- %s via %s', $provide['capabilityId'], $provide['provider']);
                }
            }
            $lines[] = '';
        }

        return implode("\n", $lines);
    }
}
