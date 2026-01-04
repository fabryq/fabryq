<?php

/**
 * Console command that adds template scaffolding to a component.
 *
 * @package   Fabryq\Cli\Command
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Command;

use Fabryq\Cli\Error\CliExitCode;
use Fabryq\Cli\Error\ProjectStateError;
use Fabryq\Cli\Error\UserError;
use Fabryq\Cli\Lock\WriteLock;
use Fabryq\Runtime\Registry\AppRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Adds Resources/templates to a component.
 */
#[AsCommand(
    name: 'fabryq:component:add:templates',
    description: 'Add templates scaffolding to a component.'
)]
final class ComponentAddTemplatesCommand extends AbstractFabryqCommand
{
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly AppRegistry $appRegistry,
        private readonly string $projectDir,
        private readonly WriteLock $writeLock,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('component', InputArgument::REQUIRED, 'Component folder name.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Plan changes without writing files.')
            ->setDescription('Add templates scaffolding to a component.');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $componentName = $this->requireStringArgument($input, 'component');
        $dryRun = (bool) $input->getOption('dry-run');

        $component = $this->findComponent($componentName);
        if ($component === null) {
            throw new ProjectStateError(sprintf('Component "%s" not found.', $componentName));
        }

        $targetDir = $component['path'] . '/Resources/templates';
        $keepPath = $targetDir . '/.keep';

        if ($dryRun) {
            $io->title('Dry-run: fabryq:component:add:templates');
            $io->listing([$this->normalizePath($targetDir), $this->normalizePath($keepPath)]);
            $io->success('No files were written.');
            return CliExitCode::SUCCESS;
        }

        if ($this->filesystem->exists($targetDir)) {
            $io->success('Templates directory already exists.');
            return CliExitCode::SUCCESS;
        }

        $this->writeLock->acquire();
        try {
            $this->filesystem->mkdir($targetDir);
            $this->filesystem->touch($keepPath);
        } finally {
            $this->writeLock->release();
        }

        $io->success('Templates scaffolding created.');

        return CliExitCode::SUCCESS;
    }

    /**
     * @param string $componentName
     *
     * @return array{path: string, appId: string}|null
     */
    private function findComponent(string $componentName): ?array
    {
        $matches = [];
        foreach ($this->appRegistry->getApps() as $app) {
            foreach ($app->components as $component) {
                if ($component->name === $componentName || basename($component->path) === $componentName) {
                    $matches[] = ['path' => $component->path, 'appId' => $app->manifest->appId];
                }
            }
        }

        if ($matches === []) {
            return null;
        }

        if (count($matches) > 1) {
            throw new UserError(sprintf('Component "%s" is ambiguous across apps.', $componentName));
        }

        return $matches[0];
    }

    private function normalizePath(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);
        $root = str_replace('\\', '/', $this->projectDir);
        if (str_starts_with($normalized, $root . '/')) {
            return substr($normalized, strlen($root) + 1);
        }

        return ltrim($normalized, '/');
    }
}
