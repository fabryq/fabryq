<?php

/**
 * Console command that generates a new Fabryq component skeleton.
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
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Creates a component within an existing app.
 */
#[AsCommand(
    name: 'fabryq:component:create',
    description: 'Create a new Fabryq component within an app.'
)]
final class ComponentCreateCommand extends AbstractFabryqCommand
{
    /**
     * @param Filesystem       $filesystem Filesystem abstraction for writing files.
     * @param AppRegistry      $appRegistry Registry of discovered apps.
     * @param ComponentSlugger $slugger     Slug generator for component names.
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
         * Slug generator used to normalize component slugs.
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
            ->addArgument('app', InputArgument::REQUIRED, 'Application folder name (PascalCase).')
            ->addArgument('component', InputArgument::REQUIRED, 'Component name (PascalCase).')
            ->addOption('with-public', null, InputOption::VALUE_NONE, 'Add Resources/public directory.')
            ->addOption('with-templates', null, InputOption::VALUE_NONE, 'Add Resources/templates directory.')
            ->addOption('with-translations', null, InputOption::VALUE_NONE, 'Add Resources/translations directory.')
            ->setDescription('Create a new Fabryq component within an app.');
        parent::configure();
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $appName = $this->requireStringArgument($input, 'app');
        $componentName = $this->requireStringArgument($input, 'component');
        $dryRun = (bool) $input->getOption('dry-run');

        if (!preg_match('/^[A-Z][A-Za-z0-9]*$/', $componentName)) {
            throw new UserError(sprintf('Invalid component name "%s".', $componentName));
        }

        $app = $this->findApp($appName);
        if ($app === null) {
            throw new ProjectStateError(sprintf('App "%s" not found.', $appName));
        }

        $slug = $this->slugger->slug($componentName);
        foreach ($app->components as $component) {
            if ($component->slug === $slug) {
                throw new ProjectStateError(sprintf('Component slug "%s" already exists in app %s.', $slug, $app->manifest->appId));
            }
        }

        $componentPath = $this->projectDir.'/src/Apps/'.basename($app->path).'/'.$componentName;
        if ($this->filesystem->exists($componentPath)) {
            throw new ProjectStateError(sprintf('Component path "%s" already exists.', $componentPath));
        }

        $resourceDirs = [$componentPath.'/Resources/config'];
        if ($input->getOption('with-public')) {
            $resourceDirs[] = $componentPath.'/Resources/public';
        }
        if ($input->getOption('with-templates')) {
            $resourceDirs[] = $componentPath.'/Resources/templates';
        }
        if ($input->getOption('with-translations')) {
            $resourceDirs[] = $componentPath.'/Resources/translations';
        }

        $planned = [
            $componentPath,
            $componentPath.'/Controller',
            $componentPath.'/Service',
        ];
        foreach ($resourceDirs as $dir) {
            $planned[] = $dir;
            $planned[] = $dir.'/.keep';
        }

        if ($dryRun) {
            $io->title('Dry-run: fabryq:component:create');
            $io->listing($planned);
            $io->success('No files were written.');
            return CliExitCode::SUCCESS;
        }

        $this->writeLock->acquire();

        try {
            $this->filesystem->mkdir([
                $componentPath.'/Controller',
                $componentPath.'/Service',
                ...$resourceDirs,
            ]);

            foreach ($resourceDirs as $dir) {
                $this->filesystem->touch($dir.'/.keep');
            }
        } finally {
            $this->writeLock->release();
        }

        $io->success(sprintf('Component "%s" created in app %s.', $componentName, $app->manifest->appId));

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
}
