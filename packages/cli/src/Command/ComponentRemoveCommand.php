<?php

/**
 * Console command that removes a Fabryq component.
 *
 * @package   Fabryq\Cli\Command
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Command;

use Fabryq\Cli\Analyzer\ReferenceScanner;
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
 * Removes a component directory after dependency checks.
 */
#[AsCommand(
    name: 'fabryq:component:remove',
    description: 'Remove a Fabryq component.'
)]
final class ComponentRemoveCommand extends AbstractFabryqCommand
{
    /**
     * @param Filesystem       $filesystem Filesystem abstraction.
     * @param AppRegistry      $appRegistry App registry.
     * @param ReferenceScanner $scanner    Reference scanner.
     * @param string           $projectDir Project directory.
     * @param WriteLock        $writeLock  Write lock guard.
     */
    public function __construct(
        private readonly Filesystem       $filesystem,
        private readonly AppRegistry      $appRegistry,
        private readonly ReferenceScanner $scanner,
        private readonly string           $projectDir,
        private readonly WriteLock        $writeLock,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('component', InputArgument::REQUIRED, 'Component folder name.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Plan changes without writing files.')
            ->setDescription('Remove a Fabryq component.');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $componentName = (string) $input->getArgument('component');
        $dryRun = (bool) $input->getOption('dry-run');

        if ($componentName === '') {
            throw new UserError('Component name is required.');
        }

        $matches = [];
        foreach ($this->appRegistry->getApps() as $app) {
            foreach ($app->components as $component) {
                if ($component->name === $componentName || basename($component->path) === $componentName) {
                    $matches[] = ['app' => $app, 'component' => $component];
                }
            }
        }

        if ($matches === []) {
            throw new ProjectStateError(sprintf('Component "%s" not found.', $componentName));
        }

        if (count($matches) > 1) {
            throw new UserError(sprintf('Component "%s" is ambiguous across apps.', $componentName));
        }

        $app = $matches[0]['app'];
        $component = $matches[0]['component'];
        $appFolder = basename($app->path);
        $prefix = sprintf('App\\%s\\%s\\', $appFolder, $component->name);

        $references = $this->scanner->findReferences($this->projectDir, $prefix, $component->path);
        if ($references !== []) {
            $io->error('Component removal blocked by existing references.');
            foreach ($references as $reference) {
                $io->text(sprintf(
                    '- %s:%d (%s)',
                    $this->normalizePath($reference['file']),
                    $reference['line'],
                    $reference['symbol']
                ));
            }
            return CliExitCode::PROJECT_STATE_ERROR;
        }

        if ($dryRun) {
            $io->title('Dry-run: fabryq:component:remove');
            $io->listing([$this->normalizePath($component->path)]);
            $io->success('No files were removed.');
            return CliExitCode::SUCCESS;
        }

        $this->writeLock->acquire();
        try {
            $this->filesystem->remove($component->path);
        } finally {
            $this->writeLock->release();
        }

        $io->success(sprintf('Component "%s" removed from app %s.', $component->name, $app->manifest->appId));

        return CliExitCode::SUCCESS;
    }

    /**
     * Normalize a path to be project-relative.
     *
     * @param string $path
     *
     * @return string
     */
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
