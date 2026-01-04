<?php

/**
 * Console command that generates CRUD scaffolding for a resource.
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
 * Generates CRUD use cases, DTOs, and a thin controller.
 */
#[AsCommand(
    name: 'fabryq:crud:create',
    description: 'Create CRUD scaffolding for a resource inside an app.'
)]
final class CrudCreateCommand extends AbstractFabryqCommand
{
    /**
     * @param Filesystem    $filesystem Filesystem abstraction.
     * @param AppRegistry   $appRegistry App registry.
     * @param ComponentSlugger $slugger Slug generator for resource routes.
     * @param string        $projectDir Project directory.
     * @param WriteLock     $writeLock  Write lock guard.
     */
    public function __construct(
        private readonly Filesystem       $filesystem,
        private readonly AppRegistry      $appRegistry,
        private readonly ComponentSlugger $slugger,
        private readonly string           $projectDir,
        private readonly WriteLock        $writeLock,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('app', InputArgument::REQUIRED, 'Application folder name or appId.')
            ->addArgument('resource', InputArgument::REQUIRED, 'Resource name (PascalCase).')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Plan changes without writing files.')
            ->setDescription('Create CRUD scaffolding for a resource inside an app.');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $appName = (string) $input->getArgument('app');
        $resourceName = (string) $input->getArgument('resource');
        $dryRun = (bool) $input->getOption('dry-run');

        if (!preg_match('/^[A-Z][A-Za-z0-9]*$/', $resourceName)) {
            throw new UserError(sprintf('Invalid resource name "%s".', $resourceName));
        }

        $app = $this->findApp($appName);
        if ($app === null) {
            throw new ProjectStateError(sprintf('App "%s" not found.', $appName));
        }

        $appFolder = basename($app->path);
        $componentPath = $this->projectDir . '/src/Apps/' . $appFolder . '/' . $resourceName;

        $resourceSlug = $this->slugger->slug($resourceName);
        $controllerConfig = $this->defaultControllerConfig();

        $targets = $this->buildTargets($appFolder, $resourceName, $resourceSlug, $controllerConfig, $componentPath);

        foreach ($targets['files'] as $path => $_content) {
            if ($this->filesystem->exists($path)) {
                throw new ProjectStateError(sprintf('Target already exists: %s', $path));
            }
        }

        if ($dryRun) {
            $io->title('Dry-run: fabryq:crud:create');
            $io->listing($this->normalizePaths(array_merge($targets['dirs'], array_keys($targets['files']))));
            $io->success('No files were written.');
            return CliExitCode::SUCCESS;
        }

        $this->writeLock->acquire();
        try {
            $this->filesystem->mkdir($targets['dirs']);
            foreach ($targets['files'] as $path => $content) {
                $this->filesystem->dumpFile($path, $content);
            }
        } finally {
            $this->writeLock->release();
        }

        $io->success(sprintf('CRUD scaffold created for %s in app %s.', $resourceName, $app->manifest->appId));

        return CliExitCode::SUCCESS;
    }

    /**
     * Build target directories and files for CRUD scaffolding.
     *
     * @param string               $appFolder App folder name.
     * @param string               $resourceName Resource name.
     * @param string               $resourceSlug Resource slug.
     * @param array<string, mixed> $controllerConfig Controller defaults.
     * @param string               $componentPath Component base path.
     *
     * @return array{dirs: string[], files: array<string, string>}
     */
    private function buildTargets(string $appFolder, string $resourceName, string $resourceSlug, array $controllerConfig, string $componentPath): array
    {
        $dtoDir = $componentPath . '/Dto/' . $resourceName;
        $useCaseDir = $componentPath . '/UseCase/' . $resourceName;
        $controllerDir = $componentPath . '/Controller';

        $dirs = [$componentPath, $dtoDir, $useCaseDir, $controllerDir];
        $files = [];

        $actions = ['List', 'Get', 'Create', 'Update', 'Delete'];
        foreach ($actions as $action) {
            $files[$useCaseDir . '/' . $action . $resourceName . 'UseCase.php'] = $this->renderUseCase($appFolder, $resourceName, $action);
            $files[$dtoDir . '/' . $action . $resourceName . 'Request.php'] = $this->renderRequestDto($appFolder, $resourceName, $action);
            $files[$dtoDir . '/' . $action . $resourceName . 'Response.php'] = $this->renderResponseDto($appFolder, $resourceName, $action);
        }

        $files[$controllerDir . '/' . $resourceName . 'Controller.php'] = $this->renderController(
            $appFolder,
            $resourceName,
            $resourceSlug,
            $controllerConfig
        );

        return ['dirs' => $dirs, 'files' => $files];
    }

    /**
     * Render a CRUD controller.
     *
     * @param string               $appFolder App folder.
     * @param string               $resourceName Resource name.
     * @param string               $resourceSlug Resource slug.
     * @param array<string, mixed> $config Controller config.
     *
     * @return string
     */
    private function renderController(string $appFolder, string $resourceName, string $resourceSlug, array $config): string
    {
        $namespace = sprintf('App\\%s\\%s\\Controller', $appFolder, $resourceName);
        $dtoNamespace = sprintf('App\\%s\\%s\\Dto\\%s', $appFolder, $resourceName, $resourceName);
        $useCaseNamespace = sprintf('App\\%s\\%s\\UseCase\\%s', $appFolder, $resourceName, $resourceName);

        $routePrefix = (string) ($config['route_prefix'] ?? '');
        $routeNamePrefix = (string) ($config['route_name_prefix'] ?? '');
        $defaultFormat = (string) ($config['default_format'] ?? 'json');

        $security = $config['security'] ?? [];
        $securityEnabled = (bool) ($security['enabled'] ?? false);
        $securityAttribute = (string) ($security['attribute'] ?? '');

        $templates = $config['templates'] ?? [];
        $templatesEnabled = (bool) ($templates['enabled'] ?? false);
        $templateNamespace = (string) ($templates['namespace'] ?? '');

        $translations = $config['translations'] ?? [];
        $translationsEnabled = (bool) ($translations['enabled'] ?? false);
        $translationDomain = (string) ($translations['domain'] ?? 'messages');

        $templatePrefix = $templateNamespace === '' ? '' : '@' . trim($templateNamespace, '@') . '/';

        $imports = [
            'Fabryq\\Runtime\\Context\\FabryqContext',
            'Fabryq\\Runtime\\Controller\\AbstractFabryqController',
            'Symfony\\Component\\HttpFoundation\\Response',
            'Symfony\\Component\\HttpFoundation\\Request',
            'Symfony\\Component\\Routing\\Attribute\\Route',
        ];

        if ($securityEnabled && $securityAttribute !== '') {
            $imports[] = 'Symfony\\Component\\Security\\Http\\Attribute\\IsGranted';
        }

        $useCases = [
            'List' => 'list',
            'Get' => 'get',
            'Create' => 'create',
            'Update' => 'update',
            'Delete' => 'delete',
        ];

        $useCaseProps = [];
        foreach ($useCases as $action => $method) {
            $useCaseClass = $action . $resourceName . 'UseCase';
            $useCaseProps[] = sprintf('private readonly %s\\%s $%s%sUseCase', $useCaseNamespace, $useCaseClass, $method, $resourceName);
        }

        $imports = array_merge($imports, [
            $useCaseNamespace . '\\List' . $resourceName . 'UseCase',
            $useCaseNamespace . '\\Get' . $resourceName . 'UseCase',
            $useCaseNamespace . '\\Create' . $resourceName . 'UseCase',
            $useCaseNamespace . '\\Update' . $resourceName . 'UseCase',
            $useCaseNamespace . '\\Delete' . $resourceName . 'UseCase',
            $dtoNamespace . '\\List' . $resourceName . 'Request',
            $dtoNamespace . '\\Get' . $resourceName . 'Request',
            $dtoNamespace . '\\Create' . $resourceName . 'Request',
            $dtoNamespace . '\\Update' . $resourceName . 'Request',
            $dtoNamespace . '\\Delete' . $resourceName . 'Request',
        ]);

        sort($imports);

        $methods = [];
        $actionsMeta = [
            'list' => ['GET', '/' . $resourceSlug],
            'get' => ['GET', '/' . $resourceSlug . '/{id}'],
            'create' => ['POST', '/' . $resourceSlug],
            'update' => ['PUT', '/' . $resourceSlug . '/{id}'],
            'delete' => ['DELETE', '/' . $resourceSlug . '/{id}'],
        ];

        foreach ($actionsMeta as $action => $meta) {
            [$httpMethod, $path] = $meta;
            $routePath = $this->buildRoutePath($routePrefix, $path);
            $routeName = $this->buildRouteName($routeNamePrefix, $resourceSlug . '.' . $action);
            $format = $defaultFormat !== '' ? ", format: '" . addslashes($defaultFormat) . "'" : '';

            $routeAttribute = sprintf(
                "#[Route('%s', name: '%s', methods: ['%s']%s)]",
                addslashes($routePath),
                addslashes($routeName),
                $httpMethod,
                $format
            );

            $signatureParts = [];
            $bodyLines = [];
            $requestVar = $action . $resourceName . 'Request';
            $useCaseProp = $action . $resourceName . 'UseCase';

            if (in_array($action, ['create', 'update'], true)) {
                $signatureParts[] = 'Request $request';
                $bodyLines[] = '$payload = $request->request->all();';
            }

            if (in_array($action, ['get', 'update', 'delete'], true)) {
                array_unshift($signatureParts, 'string $id');
            }

            if ($action === 'list') {
                $bodyLines[] = sprintf('$response = ($this->%s)(new %s());', $useCaseProp, $requestVar);
            } elseif ($action === 'get') {
                $bodyLines[] = sprintf('$response = ($this->%s)(new %s($id));', $useCaseProp, $requestVar);
            } elseif ($action === 'create') {
                $bodyLines[] = sprintf('$response = ($this->%s)(new %s($payload));', $useCaseProp, $requestVar);
            } elseif ($action === 'update') {
                $bodyLines[] = sprintf('$response = ($this->%s)(new %s($id, $payload));', $useCaseProp, $requestVar);
            } else {
                $bodyLines[] = sprintf('$response = ($this->%s)(new %s($id));', $useCaseProp, $requestVar);
            }

            $title = ucfirst($action) . ' ' . $resourceName;
            $translationKey = $resourceSlug . '.' . $action;

            if ($templatesEnabled) {
                $template = $templatePrefix . $resourceSlug . '/' . $action . '.html.twig';
                if ($translationsEnabled) {
                    $bodyLines[] = sprintf(
                        "return \$this->render('%s', ['response' => \$response, 'title' => \$this->trans('%s', [], '%s')]);",
                        $template,
                        addslashes($translationKey),
                        addslashes($translationDomain)
                    );
                } else {
                    $bodyLines[] = sprintf(
                        "return \$this->render('%s', ['response' => \$response, 'title' => '%s']);",
                        $template,
                        addslashes($title)
                    );
                }
            } else {
                $payload = '$response->toArray()';
                if ($translationsEnabled) {
                    $bodyLines[] = sprintf(
                        "return \$this->json(['message' => \$this->trans('%s', [], '%s'), 'data' => %s]);",
                        addslashes($translationKey),
                        addslashes($translationDomain),
                        $payload
                    );
                } else {
                    $bodyLines[] = sprintf(
                        'return $this->json([\'data\' => %s]);',
                        $payload
                    );
                }
            }

            $methods[] = $this->renderControllerMethod($action, $routeAttribute, $signatureParts, $bodyLines);
        }

        $securityAttributeLine = '';
        if ($securityEnabled && $securityAttribute !== '') {
            $securityAttributeLine = "#[IsGranted('" . addslashes($securityAttribute) . "')]\n";
        }

        $constructorProps = implode(",\n        ", $useCaseProps);

        return "<?php\n\n".
            "declare(strict_types=1);\n\n".
            "namespace ".$namespace.";\n\n".
            implode("\n", array_map(static fn(string $import): string => 'use '.$import.';', $imports))."\n\n".
            $securityAttributeLine.
            "final class ".$resourceName."Controller extends AbstractFabryqController\n".
            "{\n".
            "    public function __construct(\n".
            "        FabryqContext \$ctx,\n".
            "        ".$constructorProps.",\n".
            "    ) {\n".
            "        parent::__construct(\$ctx);\n".
            "    }\n\n".
            implode("\n\n", $methods).
            "\n}\n";
    }

    /**
     * Default controller configuration used when no project config is applied.
     *
     * @return array<string, mixed>
     */
    private function defaultControllerConfig(): array
    {
        return [
            'route_prefix' => '',
            'route_name_prefix' => '',
            'default_format' => 'json',
            'security' => [
                'enabled' => false,
                'attribute' => '',
            ],
            'templates' => [
                'enabled' => false,
                'namespace' => '',
            ],
            'translations' => [
                'enabled' => false,
                'domain' => 'messages',
            ],
        ];
    }

    /**
     * Render a controller method.
     *
     * @param string        $action Action name.
     * @param string        $routeAttribute Route attribute line.
     * @param array<int, string> $signatureParts Parameters.
     * @param array<int, string> $bodyLines Body lines.
     *
     * @return string
     */
    private function renderControllerMethod(string $action, string $routeAttribute, array $signatureParts, array $bodyLines): string
    {
        $signature = $signatureParts === [] ? '' : implode(', ', $signatureParts);
        $body = implode("\n        ", $bodyLines);

        return "    ".$routeAttribute."\n".
            "    public function ".$action."(".$signature."): Response\n".
            "    {\n".
            "        ".$body."\n".
            "    }";
    }

    /**
     * Render a use case class.
     *
     * @param string $appFolder App folder.
     * @param string $resourceName Resource name.
     * @param string $action Action name.
     *
     * @return string
     */
    private function renderUseCase(string $appFolder, string $resourceName, string $action): string
    {
        $namespace = sprintf('App\\%s\\%s\\UseCase\\%s', $appFolder, $resourceName, $resourceName);
        $dtoNamespace = sprintf('App\\%s\\%s\\Dto\\%s', $appFolder, $resourceName, $resourceName);
        $className = $action . $resourceName . 'UseCase';
        $requestClass = $action . $resourceName . 'Request';
        $responseClass = $action . $resourceName . 'Response';
        $actionLower = strtolower($action);

        $responseReturn = match ($action) {
            'Delete' => 'new '.$responseClass.'(false)',
            default => 'new '.$responseClass.'([])',
        };

        return "<?php\n\n".
            "declare(strict_types=1);\n\n".
            "namespace ".$namespace.";\n\n".
            "use ".$dtoNamespace."\\".$requestClass.";\n".
            "use ".$dtoNamespace."\\".$responseClass.";\n".
            "use Fabryq\\Runtime\\UseCase\\AbstractFabryqUseCase;\n\n".
            "final class ".$className." extends AbstractFabryqUseCase\n".
            "{\n".
            "    public function __invoke(".$requestClass." \$request): ".$responseClass."\n".
            "    {\n".
            "        // TODO: implement ".$actionLower." ".$resourceName." use case.\n".
            "        return ".$responseReturn.";\n".
            "    }\n".
            "}\n";
    }

    /**
     * Render a request DTO.
     *
     * @param string $appFolder App folder.
     * @param string $resourceName Resource name.
     * @param string $action Action name.
     *
     * @return string
     */
    private function renderRequestDto(string $appFolder, string $resourceName, string $action): string
    {
        $namespace = sprintf('App\\%s\\%s\\Dto\\%s', $appFolder, $resourceName, $resourceName);
        $className = $action . $resourceName . 'Request';

        $constructor = match ($action) {
            'Get', 'Delete' => 'public function __construct(public string $id) {}',
            'Update' => 'public function __construct(public string $id, public array $payload = []) {}',
            'Create' => 'public function __construct(public array $payload = []) {}',
            default => 'public function __construct(public int $page = 1, public int $limit = 25) {}',
        };

        return "<?php\n\n".
            "declare(strict_types=1);\n\n".
            "namespace ".$namespace.";\n\n".
            "final class ".$className."\n".
            "{\n".
            "    ".$constructor."\n".
            "}\n";
    }

    /**
     * Render a response DTO.
     *
     * @param string $appFolder App folder.
     * @param string $resourceName Resource name.
     * @param string $action Action name.
     *
     * @return string
     */
    private function renderResponseDto(string $appFolder, string $resourceName, string $action): string
    {
        $namespace = sprintf('App\\%s\\%s\\Dto\\%s', $appFolder, $resourceName, $resourceName);
        $className = $action . $resourceName . 'Response';

        $properties = match ($action) {
            'Delete' => ['bool $deleted = false', "return ['deleted' => \$this->deleted];"],
            default => ['array $data = []', "return ['data' => \$this->data];"],
        };

        return "<?php\n\n".
            "declare(strict_types=1);\n\n".
            "namespace ".$namespace.";\n\n".
            "final class ".$className."\n".
            "{\n".
            "    public function __construct(public ".$properties[0].")\n".
            "    {\n".
            "    }\n\n".
            "    public function toArray(): array\n".
            "    {\n".
            "        ".$properties[1]."\n".
            "    }\n".
            "}\n";
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
     * Build a route path with prefix.
     *
     * @param string $prefix Prefix.
     * @param string $path   Base path.
     *
     * @return string
     */
    private function buildRoutePath(string $prefix, string $path): string
    {
        $prefix = trim($prefix);
        if ($prefix === '') {
            return $path;
        }

        $prefix = '/' . trim($prefix, '/');
        $path = '/' . ltrim($path, '/');

        return rtrim($prefix, '/') . $path;
    }

    /**
     * Build a route name with prefix.
     *
     * @param string $prefix Prefix.
     * @param string $name   Base name.
     *
     * @return string
     */
    private function buildRouteName(string $prefix, string $name): string
    {
        if ($prefix === '') {
            return $name;
        }

        return $prefix . $name;
    }

    /**
     * Normalize paths to be project-relative.
     *
     * @param array<int, string> $paths
     *
     * @return array<int, string>
     */
    private function normalizePaths(array $paths): array
    {
        $normalized = [];
        foreach ($paths as $path) {
            $normalized[] = $this->normalizePath($path);
        }

        return $normalized;
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
