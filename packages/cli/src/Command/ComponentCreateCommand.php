<?php

/**
 * Console command that generates a new Fabryq component skeleton.
 *
 * @package   Fabryq\Cli\Command
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Command;

use Fabryq\Runtime\Registry\AppRegistry;
use Fabryq\Runtime\Util\ComponentSlugger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
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
final class ComponentCreateCommand extends Command
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
    ) {
        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this
            ->addArgument('app', InputArgument::REQUIRED, 'Application folder name (PascalCase).')
            ->addArgument('component', InputArgument::REQUIRED, 'Component name (PascalCase).')
            ->addOption('with-public', null, InputOption::VALUE_NONE, 'Add Resources/public directory.')
            ->addOption('with-templates', null, InputOption::VALUE_NONE, 'Add Resources/templates directory.')
            ->addOption('with-translations', null, InputOption::VALUE_NONE, 'Add Resources/translations directory.')
            ->setDescription('Create a new Fabryq component within an app.');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $appName = (string) $input->getArgument('app');
        $componentName = (string) $input->getArgument('component');

        if (!preg_match('/^[A-Z][A-Za-z0-9]*$/', $componentName)) {
            $io->error(sprintf('Invalid component name "%s".', $componentName));
            return Command::FAILURE;
        }

        $app = $this->findApp($appName);
        if ($app === null) {
            $io->error(sprintf('App "%s" not found.', $appName));
            return Command::FAILURE;
        }

        $slug = $this->slugger->slug($componentName);
        foreach ($app->components as $component) {
            if ($component->slug === $slug) {
                $io->error(sprintf('Component slug "%s" already exists in app %s.', $slug, $app->manifest->appId));
                return Command::FAILURE;
            }
        }

        $componentPath = $this->projectDir.'/src/Apps/'.basename($app->path).'/'.$componentName;
        if ($this->filesystem->exists($componentPath)) {
            $io->error(sprintf('Component path "%s" already exists.', $componentPath));
            return Command::FAILURE;
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

        $this->filesystem->mkdir([
            $componentPath.'/Controller',
            $componentPath.'/Service',
            ...$resourceDirs,
        ]);

        foreach ($resourceDirs as $dir) {
            $this->filesystem->touch($dir.'/.keep');
        }

        $io->success(sprintf('Component "%s" created in app %s.', $componentName, $app->manifest->appId));

        return Command::SUCCESS;
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
