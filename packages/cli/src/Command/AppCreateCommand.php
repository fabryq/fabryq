<?php

/**
 * Console command that generates a new Fabryq app skeleton.
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
use Fabryq\Runtime\Util\ComponentSlugger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Creates a new application skeleton with manifest and resources.
 */
#[AsCommand(
    name: 'fabryq:app:create',
    description: 'Create a new Fabryq application skeleton.'
)]
final class AppCreateCommand extends AbstractFabryqCommand
{
    /**
     * @param Filesystem       $filesystem Filesystem abstraction for writing files.
     * @param AppRegistry      $appRegistry Registry of discovered apps.
     * @param ComponentSlugger $slugger     Slug generator for app ids.
     * @param string           $projectDir  Absolute project directory.
     */
    public function __construct(
        /**
         * Filesystem abstraction used for writing.
         *
         * @var Filesystem
         */
        private readonly Filesystem       $filesystem,
        /**
         * Registry of discovered applications.
         *
         * @var AppRegistry
         */
        private readonly AppRegistry      $appRegistry,
        /**
         * Slug generator used to normalize app ids.
         *
         * @var ComponentSlugger
         */
        private readonly ComponentSlugger $slugger,
        /**
         * Absolute project directory.
         *
         * @var string
         */
        private readonly string           $projectDir,
        /**
         * Write lock guard.
         *
         * @var WriteLock
         */
        private readonly WriteLock        $writeLock,
    ) {
        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Plan changes without writing files.')
            ->addArgument('name', InputArgument::REQUIRED, 'Application name (PascalCase folder name).')
            ->addOption('app-id', null, InputOption::VALUE_REQUIRED, 'App id (defaults to slug of name).')
            ->addOption('mount', null, InputOption::VALUE_REQUIRED, 'Mountpoint starting with "/" (optional).')
            ->setDescription('Create a new Fabryq application skeleton.');
        parent::configure();
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = (string) $input->getArgument('name');
        $dryRun = (bool) $input->getOption('dry-run');

        if ($name === '' || str_contains($name, '/') || str_contains($name, '\\')) {
            throw new UserError('Invalid app name.');
        }

        $appId = (string)($input->getOption('app-id') ?? '');
        if ($appId === '') {
            $appId = $this->slugger->slug($name);
        }

        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $appId)) {
            throw new UserError(sprintf('Invalid appId "%s".', $appId));
        }

        $mount = $input->getOption('mount');
        $mountpoint = $mount === null || $mount === '' ? null : (string) $mount;
        if ($mountpoint !== null) {
            $valid = str_starts_with($mountpoint, '/')
                && ($mountpoint === '/' || !str_ends_with($mountpoint, '/'))
                && !str_contains($mountpoint, '//');
            if (!$valid) {
                throw new UserError(sprintf('Invalid mountpoint "%s".', $mountpoint));
            }
        }

        foreach ($this->appRegistry->getApps() as $app) {
            if ($app->manifest->appId === $appId) {
                throw new ProjectStateError(sprintf('AppId "%s" already exists.', $appId));
            }
            if (basename($app->path) === $name) {
                throw new ProjectStateError(sprintf('App folder "%s" already exists.', $name));
            }
            if ($mountpoint !== null && $app->manifest->mountpoint === $mountpoint) {
                throw new ProjectStateError(sprintf('Mountpoint "%s" is already used.', $mountpoint));
            }
        }

        $appPath = $this->projectDir.'/src/Apps/'.$name;
        if ($this->filesystem->exists($appPath)) {
            throw new ProjectStateError(sprintf('App path "%s" already exists.', $appPath));
        }

        $resourceDirs = [
            $appPath.'/Resources/config',
            $appPath.'/Resources/public',
            $appPath.'/Resources/templates',
            $appPath.'/Resources/translations',
        ];

        $manifestPath = $appPath.'/manifest.php';

        $planned = [$appPath, $manifestPath];
        foreach ($resourceDirs as $dir) {
            $planned[] = $dir;
            $planned[] = $dir.'/.keep';
        }

        if ($dryRun) {
            $io->title('Dry-run: fabryq:app:create');
            $io->listing($planned);
            $io->success('No files were written.');
            return CliExitCode::SUCCESS;
        }

        $this->writeLock->acquire();

        try {
            $this->filesystem->mkdir($resourceDirs);
            foreach ($resourceDirs as $dir) {
                $this->filesystem->touch($dir.'/.keep');
            }

            $manifest = $this->renderManifest($appId, $name, $mountpoint);
            $this->filesystem->dumpFile($manifestPath, $manifest);
        } finally {
            $this->writeLock->release();
        }

        $io->success(sprintf('App "%s" created at %s.', $appId, $appPath));

        return CliExitCode::SUCCESS;
    }

    /**
     * Render a manifest file for the new application.
     *
     * @param string      $appId      App identifier.
     * @param string      $name       App display name.
     * @param string|null $mountpoint Mountpoint or null.
     *
     * @return string Manifest PHP contents.
     */
    private function renderManifest(string $appId, string $name, ?string $mountpoint): string
    {
        $mount = $mountpoint === null ? 'null' : "'".addslashes($mountpoint)."'";

        return "<?php\n\n".
            "declare(strict_types=1);\n\n".
            "return [\n".
            "    'appId' => '".addslashes($appId)."',\n".
            "    'name' => '".addslashes($name)."',\n".
            "    'mountpoint' => ".$mount.",\n".
            "    'provides' => [],\n".
            "    'consumes' => [],\n".
            "    'events' => [\n".
            "        'publishes' => [],\n".
            "        'subscribes' => [],\n".
            "    ],\n".
            "];\n";
    }
}
