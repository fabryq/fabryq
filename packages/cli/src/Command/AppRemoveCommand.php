<?php

/**
 * Console command that removes a Fabryq app.
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
 * Removes an application directory after dependency checks.
 */
#[AsCommand(
    name: 'fabryq:app:remove',
    description: 'Remove a Fabryq application.'
)]
final class AppRemoveCommand extends AbstractFabryqCommand
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
            ->addArgument('app', InputArgument::REQUIRED, 'Application folder name or appId.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Plan changes without writing files.')
            ->setDescription('Remove a Fabryq application.');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $appName = $this->requireStringArgument($input, 'app');
        $dryRun = (bool) $input->getOption('dry-run');

        $app = $this->findApp($appName);
        if ($app === null) {
            throw new ProjectStateError(sprintf('App "%s" not found.', $appName));
        }

        $appFolder = basename($app->path);
        $prefix = 'App\\' . $appFolder . '\\';
        $references = $this->scanner->findReferences($this->projectDir, $prefix, $app->path);
        if ($references !== []) {
            $io->error('App removal blocked by existing references.');
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
            $io->title('Dry-run: fabryq:app:remove');
            $io->listing([$this->normalizePath($app->path)]);
            $io->success('No files were removed.');
            return CliExitCode::SUCCESS;
        }

        $this->writeLock->acquire();
        try {
            $this->filesystem->remove($app->path);
        } finally {
            $this->writeLock->release();
        }

        $io->success(sprintf('App "%s" removed.', $app->manifest->appId));

        return CliExitCode::SUCCESS;
    }

    /**
     * Find an app by folder name or appId.
     *
     * @param string $appName App folder name or appId.
     *
     * @return \Fabryq\Runtime\Registry\AppDefinition|null
     */
    private function findApp(string $appName): ?\Fabryq\Runtime\Registry\AppDefinition
    {
        foreach ($this->appRegistry->getApps() as $app) {
            if (basename($app->path) === $appName || $app->manifest->appId === $appName) {
                return $app;
            }
        }

        return null;
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
