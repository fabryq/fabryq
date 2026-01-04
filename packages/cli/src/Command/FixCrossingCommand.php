<?php

/**
 * Console command that fixes app crossing violations.
 *
 * @package   Fabryq\Cli\Command
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Command;

use Fabryq\Cli\Analyzer\Verifier;
use Fabryq\Cli\Error\CliExitCode;
use Fabryq\Cli\Error\ProjectStateError;
use Fabryq\Cli\Error\UserError;
use Fabryq\Cli\Fix\FixMode;
use Fabryq\Cli\Fix\FixRunLogger;
use Fabryq\Cli\Fix\FixSelection;
use Fabryq\Cli\Fix\ImportPruner;
use Fabryq\Cli\Lock\WriteLock;
use Fabryq\Cli\Report\Finding;
use Fabryq\Cli\Report\FindingIdGenerator;
use Fabryq\Runtime\Attribute\FabryqProvider;
use Fabryq\Runtime\Registry\AppDefinition;
use Fabryq\Runtime\Registry\AppRegistry;
use Fabryq\Runtime\Util\ComponentSlugger;
use PhpParser\Lexer\Emulative;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitor\ParentConnectingVisitor;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Fixes cross-app references by introducing bridge contracts and providers.
 */
#[AsCommand(
    name: 'fabryq:fix:crossing',
    description: 'Fix cross-app references by generating bridges.'
)]
final class FixCrossingCommand extends AbstractFabryqCommand
{
    private const ENTITY_TO_INTERFACE_RULE = 'crossing.entity_to_interface';

    /**
     * @var bool
     */
    private bool $pruneUnresolvableImports = false;

    /**
     * @var ImportPruner|null
     */
    private ?ImportPruner $importPruner = null;
    /**
     * @param Verifier           $verifier    Verification analyzer.
     * @param AppRegistry        $appRegistry Application registry.
     * @param FixRunLogger       $runLogger   Fix run logger.
     * @param FindingIdGenerator $idGenerator Finding ID generator.
     * @param Filesystem         $filesystem  Filesystem abstraction.
     * @param ComponentSlugger   $slugger     Slugger for contract names.
     * @param string             $projectDir  Absolute project directory.
     */
    public function __construct(
        /**
         * Verification analyzer.
         *
         * @var Verifier
         */
        private readonly Verifier           $verifier,
        /**
         * Application registry.
         *
         * @var AppRegistry
         */
        private readonly AppRegistry        $appRegistry,
        /**
         * Fix run logger.
         *
         * @var FixRunLogger
         */
        private readonly FixRunLogger       $runLogger,
        /**
         * Finding ID generator.
         *
         * @var FindingIdGenerator
         */
        private readonly FindingIdGenerator $idGenerator,
        /**
         * Filesystem abstraction.
         *
         * @var Filesystem
         */
        private readonly Filesystem         $filesystem,
        /**
         * Slugger for contract names.
         *
         * @var ComponentSlugger
         */
        private readonly ComponentSlugger   $slugger,
        /**
         * Absolute project directory.
         *
         * @var string
         */
        private readonly string             $projectDir,
        /**
         * Write lock guard.
         *
         * @var WriteLock
         */
        private readonly WriteLock          $writeLock,
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
            ->addOption('apply', null, InputOption::VALUE_NONE, 'Apply changes to disk.')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Select all crossings.')
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'Filter by file path.')
            ->addOption('symbol', null, InputOption::VALUE_REQUIRED, 'Filter by symbol.')
            ->addOption('finding', null, InputOption::VALUE_REQUIRED, 'Filter by finding id.')
            ->addOption('prune-unresolvable-imports', null, InputOption::VALUE_NONE, 'Remove unresolvable imports (requires vendor/autoload.php).')
            ->setDescription('Fix cross-app references by generating bridges.');
        parent::configure();
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $mode = $this->resolveMode($input);
        $this->initImportPruner($input);

        try {
            $selection = FixSelection::fromInput($input);
        } catch (\InvalidArgumentException $exception) {
            throw new UserError($exception->getMessage(), previous: $exception);
        }

        $findings = $this->verifier->verify($this->projectDir);
        $crossings = array_values(
            array_filter(
                $findings,
                static fn(Finding $finding) => $finding->ruleKey === 'FABRYQ.APP.CROSSING' && $finding->autofixAvailable
            )
        );

        $selected = array_values(
            array_filter(
                $crossings,
                fn(Finding $finding) => $selection->matchesFinding($finding, $this->idGenerator)
            )
        );

        if ($selection->findingId !== null && count($selected) !== 1) {
            throw new UserError('Finding selection did not resolve to exactly one crossing.');
        }

        $targets = [];
        $planItems = [];
        $blockers = 0;
        $warnings = 0;

        foreach ($selected as $finding) {
            $plan = $this->buildPlanItem($finding);
            $planItems[] = $plan;
            if (!$plan['fixable']) {
                $blockers++;
                continue;
            }
            $targets[] = $plan['target'];
        }

        $planMarkdown = $this->renderPlan($mode, $selection, $planItems, $blockers, $warnings);

        try {
            $context = $this->runLogger->start('crossing', $mode, $planMarkdown, $selection);
        } catch (\RuntimeException $exception) {
            throw new ProjectStateError($exception->getMessage(), previous: $exception);
        }

        if ($blockers > 0) {
            $this->runLogger->finish($context, 'crossing', $mode, 'blocked', [], $blockers, $warnings);
            $io->error('Crossing fix blocked.');
            return CliExitCode::PROJECT_STATE_ERROR;
        }

        if ($mode === FixMode::DRY_RUN) {
            $this->runLogger->finish($context, 'crossing', $mode, 'ok', [], $blockers, $warnings);
            $io->success('Crossing fix plan written (dry-run).');
            return CliExitCode::SUCCESS;
        }

        $this->writeLock->acquire();

        $changedFiles = [];
        $createdInterfaces = [];
        try {
            foreach ($targets as $target) {
                $result = $this->applyTarget($target, $changedFiles, $createdInterfaces);
                if ($result !== null) {
                    $blockers++;
                    $planItems[] = $result;
                }
            }

            $resultLabel = $blockers > 0 ? 'blocked' : 'ok';
            $this->runLogger->finish($context, 'crossing', $mode, $resultLabel, $changedFiles, $blockers, $warnings, $createdInterfaces);
        } finally {
            $this->writeLock->release();
        }

        if ($blockers > 0) {
            $io->error('Crossing fix blocked.');
            return CliExitCode::PROJECT_STATE_ERROR;
        }

        $io->success('Crossing fix applied.');
        return CliExitCode::SUCCESS;
    }

    /**
     * Apply a fix target and update changed files.
     *
     * @param array<string, mixed> $target       Fix target payload.
     * @param array<int, string>   $changedFiles List of changed files.
     *
     * @return array<string, mixed>|null Blocker plan item when apply fails.
     */
    private function applyTarget(array $target, array &$changedFiles, array &$createdInterfaces): ?array
    {
        if (($target['type'] ?? null) === 'entity_to_interface') {
            return $this->applyEntityToInterface($target, $changedFiles, $createdInterfaces);
        }

        $providerApp = $target['providerApp'];
        $consumerApp = $target['consumerApp'];
        $providerAppPascal = basename($providerApp->path);
        $bridgeRoot = $this->projectDir . '/src/Components/Bridge' . $providerAppPascal;

        if ($this->filesystem->exists($bridgeRoot) && !is_dir($bridgeRoot)) {
            return $this->blockedPlan($target['findingId'], $target['finding'], 'Bridge path exists and is not a directory.');
        }

        if (!$this->filesystem->exists($bridgeRoot . '/.fabryq-bridge') && $this->filesystem->exists($bridgeRoot)) {
            return $this->blockedPlan($target['findingId'], $target['finding'], 'Bridge directory missing .fabryq-bridge marker.');
        }

        $this->filesystem->mkdir([$bridgeRoot . '/Contract', $bridgeRoot . '/Dto', $bridgeRoot . '/NoOp']);
        $this->filesystem->touch($bridgeRoot . '/.fabryq-bridge');

        $contractPath = $bridgeRoot . '/Contract/' . $target['contractName'] . '.php';
        $noopPath = $bridgeRoot . '/NoOp/' . $target['contractName'] . 'NoOp.php';
        $adapterPath = $providerApp->path . '/Service/Bridge/' . $target['contractName'] . 'Adapter.php';

        $contractContent = $this->renderContract($target);
        $noopContent = $this->renderNoOp($target);
        $adapterContent = $this->renderAdapter($target);

        $blocker = $this->writeFileIfCompatible($contractPath, $contractContent, $target);
        if ($blocker !== null) {
            return $blocker;
        }
        $changedFiles[] = $this->normalizePath($contractPath);

        $blocker = $this->writeFileIfCompatible($noopPath, $noopContent, $target);
        if ($blocker !== null) {
            return $blocker;
        }
        $changedFiles[] = $this->normalizePath($noopPath);

        $this->filesystem->mkdir(dirname($adapterPath));
        $blocker = $this->writeFileIfCompatible($adapterPath, $adapterContent, $target);
        if ($blocker !== null) {
            return $blocker;
        }
        $changedFiles[] = $this->normalizePath($adapterPath);

        foreach ($target['dtoPlan'] as $dtoSpec) {
            $dtoPath = $bridgeRoot . '/Dto/' . $dtoSpec['className'] . '.php';
            $dtoContent = $this->renderDto($target, $dtoSpec);
            $blocker = $this->writeFileIfCompatible($dtoPath, $dtoContent, $target);
            if ($blocker !== null) {
                return $blocker;
            }
            $changedFiles[] = $this->normalizePath($dtoPath);
        }

        $consumerBlocker = $this->rewriteConsumer($target);
        if ($consumerBlocker !== null) {
            return $consumerBlocker;
        }
        $changedFiles[] = $this->normalizePath($target['consumerFile']);

        $providerManifestBlocker = $this->updateManifestProvides($providerApp->manifestPath, $target);
        if ($providerManifestBlocker !== null) {
            return $providerManifestBlocker;
        }
        $changedFiles[] = $this->normalizePath($providerApp->manifestPath);

        $consumerManifestBlocker = $this->updateManifestConsumes($consumerApp->manifestPath, $target);
        if ($consumerManifestBlocker !== null) {
            return $consumerManifestBlocker;
        }
        $changedFiles[] = $this->normalizePath($consumerApp->manifestPath);

        return null;
    }

    /**
     * Build a blocked plan entry.
     *
     * @param string        $findingId Finding id.
     * @param Finding|array $finding   Finding data.
     * @param string        $reason    Blocker reason.
     *
     * @return array<string, mixed>
     */
    private function blockedPlan(string $findingId, Finding|array $finding, string $reason): array
    {
        return [
            'id' => $findingId,
            'fixable' => false,
            'reason' => $reason,
            'finding' => $finding,
        ];
    }

    /**
     * Build contract name from provider class.
     *
     * @param string $fqcn Provider class FQCN.
     *
     * @return string Contract interface name.
     */
    private function buildContractName(string $fqcn): string
    {
        $base = basename(str_replace('\\', '/', $fqcn));
        if (str_ends_with($base, 'Interface')) {
            return $base;
        }

        return $base . 'Interface';
    }

    /**
     * Build contract slug from contract name.
     *
     * @param string $contractName Contract name.
     *
     * @return string Slug.
     */
    private function buildContractSlug(string $contractName): string
    {
        $base = str_ends_with($contractName, 'Interface')
            ? substr($contractName, 0, -9)
            : $contractName;

        return $this->slugger->slug($base);
    }

    /**
     * Build a plan item for a crossing finding.
     *
     * @param Finding $finding Crossing finding.
     *
     * @return array<string, mixed> Plan item data.
     */
    private function buildPlanItem(Finding $finding): array
    {
        $findingId = $this->idGenerator->generate($finding);
        $details = $finding->details['primary'] ?? '';
        [$fqcn, $kind] = array_pad(explode('|', (string)$details, 2), 2, null);
        $kind = $kind ?? 'unknown';

        if ($fqcn === '' || $kind === null) {
            return $this->blockedPlan($findingId, $finding, 'Missing crossing details.');
        }

        if (!in_array($kind, ['use', 'typehint', 'new'], true)) {
            return $this->blockedPlan($findingId, $finding, sprintf('Reference kind "%s" is not autofixable.', $kind));
        }

        $consumerFile = $this->resolveAbsolutePath($finding->location?->file);
        if ($consumerFile === null || !is_file($consumerFile)) {
            return $this->blockedPlan($findingId, $finding, 'Consumer file not found.');
        }

        $consumerApp = $this->resolveAppFromFile($consumerFile);
        if ($consumerApp === null) {
            return $this->blockedPlan($findingId, $finding, 'Consumer app could not be resolved.');
        }

        $providerAppPascal = $this->extractAppSegment($fqcn);
        if ($providerAppPascal === null) {
            return $this->blockedPlan($findingId, $finding, 'Provider app could not be resolved.');
        }

        $providerApp = $this->findAppByFolder($providerAppPascal);
        if ($providerApp === null) {
            return $this->blockedPlan($findingId, $finding, 'Provider app not found.');
        }

        $entityInfo = $this->parseEntityFqcn($fqcn);
        if ($entityInfo !== null) {
            if (!in_array($kind, ['use', 'typehint'], true)) {
                return $this->blockedPlan($findingId, $finding, 'Entity references are only fixable in type hints.');
            }

            $interfaceName = $entityInfo['entity'] . 'Interface';
            $interfaceFqcn = $entityInfo['baseNamespace'] . '\\Contracts\\' . $interfaceName;
            $interfacePath = $this->buildClassPath($interfaceFqcn, $entityInfo['appFolder']);

            return [
                'id' => $findingId,
                'fixable' => true,
                'ruleKey' => self::ENTITY_TO_INTERFACE_RULE,
                'summary' => sprintf(
                    '%s -> %s (%s)',
                    $this->normalizePath($consumerFile),
                    $interfaceFqcn,
                    self::ENTITY_TO_INTERFACE_RULE
                ),
                'target' => [
                    'type' => 'entity_to_interface',
                    'findingId' => $findingId,
                    'finding' => $finding,
                    'consumerFile' => $consumerFile,
                    'consumerApp' => $consumerApp,
                    'providerApp' => $providerApp,
                    'entityFqcn' => $fqcn,
                    'entityShort' => $entityInfo['entity'],
                    'interfaceFqcn' => $interfaceFqcn,
                    'interfacePath' => $interfacePath,
                ],
            ];
        }

        $providerClassFile = $this->resolveClassFile($fqcn);
        if ($providerClassFile === null) {
            return $this->blockedPlan($findingId, $finding, 'Provider class file not found.');
        }

        $contractName = $this->buildContractName($fqcn);
        $contractSlug = $this->buildContractSlug($contractName);
        $capability = sprintf('fabryq.bridge.%s.%s', $providerApp->manifest->appId, $contractSlug);
        $contractFqcn = sprintf('App\\Components\\Bridge%s\\Contract\\%s', $providerAppPascal, $contractName);

        $methodNames = $this->extractConsumerMethodCalls($consumerFile, $fqcn);
        $signatures = $this->extractProviderSignatures($providerClassFile, $methodNames);
        if ($signatures === null) {
            return $this->blockedPlan($findingId, $finding, 'Unable to resolve provider methods.');
        }

        $dtoPlan = [];
        $mapped = $this->mapSignatures($signatures, $providerAppPascal, $dtoPlan, $reason);
        if ($mapped === null) {
            return $this->blockedPlan($findingId, $finding, $reason ?? 'Unsupported types in provider contract.');
        }

        return [
            'id' => $findingId,
            'fixable' => true,
            'summary' => sprintf('%s -> %s (%s)', $this->normalizePath($consumerFile), $contractFqcn, $capability),
            'target' => [
                'findingId' => $findingId,
                'finding' => $finding,
                'consumerFile' => $consumerFile,
                'consumerApp' => $consumerApp,
                'providerApp' => $providerApp,
                'providerFqcn' => $fqcn,
                'providerClassFile' => $providerClassFile,
                'contractName' => $contractName,
                'contractFqcn' => $contractFqcn,
                'contractSlug' => $contractSlug,
                'capability' => $capability,
                'signatures' => $mapped,
                'dtoPlan' => $dtoPlan,
            ],
        ];
    }

    /**
     * Provide default return values for NoOp providers.
     *
     * @param array<string, mixed>|null $type Type descriptor.
     *
     * @return string|null Default return expression.
     */
    private function defaultReturn(?array $type): ?string
    {
        if ($type === null) {
            return null;
        }

        $name = $type['type'] ?? '';
        if ($type['nullable']) {
            return 'null';
        }

        if ($name === 'void') {
            return null;
        }

        return match ($name) {
            'string' => "''",
            'bool' => 'false',
            'int', 'float' => '0',
            'array' => '[]',
            default => in_array($name, ['DateTimeImmutable', 'DateTimeInterface'], true) ? "new DateTimeImmutable('@0')" : 'null',
        };
    }

    /**
     * Ensure constructor injection exists for a property.
     *
     * @param Node\Stmt\Class_ $classNode    Class node.
     * @param string           $contractFqcn Contract FQCN.
     * @param string           $propertyName Property name.
     *
     * @return array<string, mixed>|null
     */
    private function ensureConstructorInjection(Node\Stmt\Class_ $classNode, string $contractFqcn, string $propertyName): ?array
    {
        foreach ($classNode->getProperties() as $property) {
            foreach ($property->props as $prop) {
                if ($prop->name->toString() === $propertyName) {
                    return null;
                }
            }
        }

        $property = new Node\Stmt\Property(
            Node\Stmt\Class_::MODIFIER_PRIVATE | Node\Stmt\Class_::MODIFIER_READONLY,
            [new Node\Stmt\PropertyProperty($propertyName)],
            [],
            new Node\Name\FullyQualified($contractFqcn) // Fix: Ensure FQCN use
        );

        $classNode->stmts = array_merge([$property], $classNode->stmts);

        $constructor = $classNode->getMethod('__construct');
        $param = new Node\Param(
            new Node\Expr\Variable($propertyName),
            null,
            new Node\Name\FullyQualified($contractFqcn) // Fix: Ensure FQCN use
        );

        $assign = new Node\Stmt\Expression(
            new Node\Expr\Assign(
                new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $propertyName),
                new Node\Expr\Variable($propertyName)
            )
        );

        if ($constructor instanceof Node\Stmt\ClassMethod) {
            $constructor->params[] = $param;
            $constructor->stmts[] = $assign;
            return null;
        }

        $constructor = new Node\Stmt\ClassMethod(
            '__construct',
            [
                'flags' => Node\Stmt\Class_::MODIFIER_PUBLIC,
                'params' => [$param],
                'stmts' => [$assign],
            ]
        );

        $classNode->stmts[] = $constructor;

        return null;
    }

    /**
     * Extract the app segment from a FQCN.
     *
     * @param string $fqcn Class name.
     *
     * @return string|null App segment.
     */
    private function extractAppSegment(string $fqcn): ?string
    {
        $parts = explode('\\', $fqcn);
        if ($parts[0] !== 'App') {
            return null;
        }

        return $parts[1] ?? null;
    }

    /**
     * Extract method call names from consumer file.
     *
     * @param string $consumerFile Consumer file path.
     * @param string $providerFqcn Provider class FQCN.
     *
     * @return string[] Method names.
     */
    private function extractConsumerMethodCalls(string $consumerFile, string $providerFqcn): array
    {
        $code = file_get_contents($consumerFile);
        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        if ($ast === null) {
            return [];
        }

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new ParentConnectingVisitor());
        $traverser->addVisitor(new NameResolver());
        $ast = $traverser->traverse($ast);

        $nodeFinder = new NodeFinder();
        $propertyNames = [];
        $variableNames = [];

        foreach ($nodeFinder->findInstanceOf($ast, Node\Stmt\Property::class) as $property) {
            $type = $this->resolveTypeDescriptor($property->type);
            if ($type !== null && $type['fqcn'] === $providerFqcn) {
                foreach ($property->props as $prop) {
                    $propertyNames[] = $prop->name->toString();
                }
            }
        }

        foreach ($nodeFinder->findInstanceOf($ast, Node\Param::class) as $param) {
            $type = $this->resolveTypeDescriptor($param->type);
            if ($type !== null && $type['fqcn'] === $providerFqcn && $param->var instanceof Node\Expr\Variable) {
                $variableNames[] = $param->var->name;
            }
        }

        foreach ($nodeFinder->findInstanceOf($ast, Node\Expr\Assign::class) as $assign) {
            if (!$assign->expr instanceof Node\Expr\New_) {
                continue;
            }
            $newType = $this->resolveTypeDescriptor($assign->expr->class);
            if ($newType !== null && $newType['fqcn'] === $providerFqcn && $assign->var instanceof Node\Expr\Variable) {
                $variableNames[] = $assign->var->name;
            }
        }

        $methods = [];
        foreach ($nodeFinder->findInstanceOf($ast, Node\Expr\MethodCall::class) as $call) {
            $methodName = $call->name instanceof Node\Identifier ? $call->name->toString() : null;
            if ($methodName === null) {
                continue;
            }

            if ($call->var instanceof Node\Expr\PropertyFetch) {
                if ($call->var->var instanceof Node\Expr\Variable && $call->var->var->name === 'this') {
                    $propName = $call->var->name instanceof Node\Identifier ? $call->var->name->toString() : null;
                    if ($propName !== null && in_array($propName, $propertyNames, true)) {
                        $methods[] = $methodName;
                    }
                }
            }

            if ($call->var instanceof Node\Expr\Variable && in_array($call->var->name, $variableNames, true)) {
                $methods[] = $methodName;
            }

            if ($call->var instanceof Node\Expr\New_) {
                $newType = $this->resolveTypeDescriptor($call->var->class);
                if ($newType !== null && $newType['fqcn'] === $providerFqcn) {
                    $methods[] = $methodName;
                }
            }
        }

        return array_values(array_unique($methods));
    }

    /**
     * Extract provider method signatures.
     *
     * @param string   $providerClassFile Provider class file.
     * @param string[] $methodNames       Method names to include.
     *
     * @return array<int, array<string, mixed>>|null
     */
    private function extractProviderSignatures(string $providerClassFile, array $methodNames): ?array
    {
        $code = file_get_contents($providerClassFile);
        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        if ($ast === null) {
            return null;
        }

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $ast = $traverser->traverse($ast);

        $nodeFinder = new NodeFinder();
        $classNode = $nodeFinder->findFirstInstanceOf($ast, Node\Stmt\Class_::class);
        if (!$classNode instanceof Node\Stmt\Class_) {
            return null;
        }

        $signatures = [];
        foreach ($classNode->getMethods() as $method) {
            $name = $method->name->toString();
            if ($name === '__construct') {
                continue;
            }
            if ($methodNames !== [] && !in_array($name, $methodNames, true)) {
                continue;
            }
            if (!$method->isPublic()) {
                continue;
            }

            $params = [];
            foreach ($method->params as $param) {
                $type = $this->resolveTypeDescriptor($param->type);
                if ($type === null && $param->type !== null) {
                    return null;
                }
                $params[] = [
                    'name' => $param->var instanceof Node\Expr\Variable ? $param->var->name : 'param',
                    'type' => $type,
                    'byRef' => $param->byRef,
                    'variadic' => $param->variadic,
                    'default' => $param->default,
                ];
            }

            $returnType = $this->resolveTypeDescriptor($method->returnType);
            if ($returnType === null && $method->returnType !== null) {
                return null;
            }

            $signatures[] = [
                'name' => $name,
                'params' => $params,
                'returnType' => $returnType,
            ];
        }

        return $signatures;
    }

    /**
     * Find an app by folder name.
     *
     * @param string $folder App folder name.
     *
     * @return AppDefinition|null
     */
    private function findAppByFolder(string $folder): ?AppDefinition
    {
        foreach ($this->appRegistry->getApps() as $app) {
            if (basename($app->path) === $folder) {
                return $app;
            }
        }

        return null;
    }

    /**
     * Format type descriptor into a PHP type string.
     *
     * @param array<string, mixed>|null $type Type descriptor.
     *
     * @return string
     */
    private function formatType(?array $type): string
    {
        if ($type === null) {
            return '';
        }

        $name = $type['fqcn'] ?? $type['type'];
        $name = ltrim($name, '\\');
        $name = $type['isBuiltin'] ? $type['type'] : '\\' . $name;

        return ($type['nullable'] ? '?' : '') . $name;
    }

    /**
     * Check for Doctrine attributes or annotations.
     *
     * @param Node $node AST node.
     *
     * @return bool
     */
    private function hasDoctrineMarkers(Node $node): bool
    {
        foreach ($node->getComments() ?? [] as $comment) {
            if (str_contains($comment->getText(), '@ORM') || str_contains($comment->getText(), '@Entity')) {
                return true;
            }
        }

        if (property_exists($node, 'attrGroups')) {
            foreach ($node->attrGroups as $group) {
                foreach ($group->attrs as $attr) {
                    $name = $attr->name->toString();
                    if (str_contains($name, 'Doctrine\\ORM\\Mapping') || str_starts_with($name, 'ORM\\')) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Load manifest data from file.
     *
     * @param string $manifestPath Manifest path.
     *
     * @return array<string, mixed>|null
     */
    private function loadManifest(string $manifestPath): ?array
    {
        $data = require $manifestPath;
        if (!is_array($data)) {
            return null;
        }

        return $data;
    }

    /**
     * Map provider signatures to contract signatures and DTO plan.
     *
     * @param array<int, array<string, mixed>> $signatures        Provider signatures.
     * @param string                           $providerAppPascal Provider app folder.
     * @param array<int, array<string, mixed>> $dtoPlan           Output DTO plan.
     * @param string|null                      $reason            Output reason when not fixable.
     *
     * @return array<int, array<string, mixed>>|null
     */
    private function mapSignatures(array $signatures, string $providerAppPascal, array &$dtoPlan, ?string &$reason): ?array
    {
        $dtoIndex = [];
        $mapped = [];

        foreach ($signatures as $signature) {
            $mappedParams = [];
            foreach ($signature['params'] as $param) {
                if ($param['type'] === null) {
                    $mappedParams[] = $param + ['contractType' => null, 'dto' => null];
                    continue;
                }

                if (!$param['type']['isBuiltin'] && !in_array($param['type']['fqcn'], ['DateTimeInterface', 'DateTimeImmutable'], true)) {
                    $reason = 'Object parameters are not autofixable.';
                    return null;
                }

                $mappedParams[] = $param + ['contractType' => $param['type'], 'dto' => null];
            }

            $returnType = $signature['returnType'];
            $mappedReturn = null;
            $dtoSpec = null;

            if ($returnType !== null && !$returnType['isBuiltin'] && !in_array($returnType['fqcn'], ['DateTimeInterface', 'DateTimeImmutable'], true)) {
                $dtoSpec = $this->resolveDtoSpec($returnType['fqcn'], $providerAppPascal, $dtoPlan, $dtoIndex, $reason);
                if ($dtoSpec === null) {
                    return null;
                }
                $mappedReturn = $returnType;
                $mappedReturn['fqcn'] = $dtoSpec['fqcn'];
                $mappedReturn['type'] = $dtoSpec['fqcn'];
                $mappedReturn['nullable'] = true;
            } else {
                $mappedReturn = $returnType;
            }

            $mapped[] = [
                'name' => $signature['name'],
                'params' => $mappedParams,
                'returnType' => $returnType,
                'contractReturnType' => $mappedReturn,
                'returnDto' => $dtoSpec,
            ];
        }

        return $mapped;
    }

    /**
     * Normalize a path relative to the project directory.
     *
     * @param string $path Path to normalize.
     *
     * @return string Normalized path.
     */
    private function normalizePath(string $path): string
    {
        return (string)$this->idGenerator->normalizePath($path);
    }

    /**
     * Render adapter source.
     *
     * @param array<string, mixed> $target Fix target.
     *
     * @return string
     */
    private function renderAdapter(array $target): string
    {
        $providerAppPascal = basename($target['providerApp']->path);
        $namespace = sprintf('App\\%s\\Service\\Bridge', $providerAppPascal);
        $contractFqcn = $target['contractFqcn'];
        $providerFqcn = $target['providerFqcn'];
        $className = $target['contractName'] . 'Adapter';

        $lines = [];
        $lines[] = '<?php';
        $lines[] = '';
        $lines[] = 'declare(strict_types=1);';
        $lines[] = '';
        $lines[] = 'namespace ' . $namespace . ';';
        $lines[] = '';
        $lines[] = 'use ' . FabryqProvider::class . ';';
        $lines[] = 'use ' . $contractFqcn . ';';
        $lines[] = 'use ' . $providerFqcn . ';';
        foreach ($target['dtoPlan'] as $dtoSpec) {
            $lines[] = 'use ' . $dtoSpec['fqcn'] . ';';
        }
        $lines[] = '';
        $lines[] = sprintf(
            '#[FabryqProvider(capability: \'%s\', contract: %s::class, priority: 0)]',
            $target['capability'],
            $target['contractName']
        );
        $lines[] = 'final class ' . $className . ' implements ' . $target['contractName'];
        $lines[] = '{';
        $lines[] = '    public function __construct(private readonly ' . $this->shortName($providerFqcn) . ' $provider)';
        $lines[] = '    {';
        $lines[] = '    }';
        $lines[] = '';

        foreach ($target['signatures'] as $signature) {
            $lines[] = $this->renderMethodSignature($signature, false, $target);
            $lines[] = '';
        }

        foreach ($target['dtoPlan'] as $dtoSpec) {
            $lines[] = $this->renderDtoMapper($dtoSpec, $target);
            $lines[] = '';
        }

        $lines[] = '}';
        $lines[] = '';

        return implode("\n", $lines);
    }

    /**
     * Render arrays with stable formatting.
     *
     * @param mixed $value  Value to render.
     * @param int   $indent Indentation level.
     *
     * @return string
     */
    private function renderArray(mixed $value, int $indent): string
    {
        $pad = str_repeat('    ', $indent);
        if (is_array($value)) {
            $isAssoc = array_keys($value) !== range(0, count($value) - 1);
            $lines = ['['];
            foreach ($value as $key => $item) {
                $line = $pad . '    ';
                if ($isAssoc) {
                    $line .= "'" . addslashes((string)$key) . "' => ";
                }
                $line .= $this->renderArray($item, $indent + 1);
                $line .= ',';
                $lines[] = $line;
            }
            $lines[] = $pad . ']';
            return implode("\n", $lines);
        }

        if (is_string($value)) {
            return "'" . addslashes($value) . "'";
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return 'null';
        }

        return (string)$value;
    }

    /**
     * Render contract interface source.
     *
     * @param array<string, mixed> $target Fix target.
     *
     * @return string
     */
    private function renderContract(array $target): string
    {
        $namespace = sprintf('App\\Components\\Bridge%s\\Contract', basename($target['providerApp']->path));
        $lines = [];
        $lines[] = '<?php';
        $lines[] = '';
        $lines[] = 'declare(strict_types=1);';
        $lines[] = '';
        $lines[] = 'namespace ' . $namespace . ';';
        $lines[] = '';
        $lines[] = 'interface ' . $target['contractName'];
        $lines[] = '{';

        foreach ($target['signatures'] as $signature) {
            $lines[] = $this->renderMethodSignature($signature, true);
        }

        $lines[] = '}';
        $lines[] = '';

        return implode("\n", $lines);
    }

    /**
     * Render DTO class source.
     *
     * @param array<string, mixed> $target  Fix target.
     * @param array<string, mixed> $dtoSpec DTO specification.
     *
     * @return string
     */
    private function renderDto(array $target, array $dtoSpec): string
    {
        $providerAppPascal = basename($target['providerApp']->path);
        $namespace = sprintf('App\\Components\\Bridge%s\\Dto', $providerAppPascal);

        $lines = [];
        $lines[] = '<?php';
        $lines[] = '';
        $lines[] = 'declare(strict_types=1);';
        $lines[] = '';
        $lines[] = 'namespace ' . $namespace . ';';
        $lines[] = '';
        $lines[] = 'final readonly class ' . $dtoSpec['className'];
        $lines[] = '{';
        $lines[] = '    public function __construct(';
        $props = [];
        foreach ($dtoSpec['properties'] as $property) {
            $type = $this->formatType($property['type']);
            $props[] = sprintf('        public %s $%s', $type, $property['name']);
        }
        $lines[] = implode(",\n", $props);
        $lines[] = '    ) {';
        $lines[] = '    }';
        $lines[] = '}';
        $lines[] = '';

        return implode("\n", $lines);
    }

    /**
     * Render DTO mapper methods.
     *
     * @param array<string, mixed> $dtoSpec DTO specification.
     * @param array<string, mixed> $target  Fix target.
     *
     * @return string
     */
    private function renderDtoMapper(array $dtoSpec, array $target): string
    {
        $domainFqcn = $dtoSpec['sourceFqcn'];
        $dtoClass = $dtoSpec['className'];
        $dtoShort = $this->shortName($dtoSpec['fqcn']);
        $domainShort = $this->shortName($domainFqcn);

        $lines = [];
        $lines[] = sprintf('    private function to%s(?%s $value): ?%s', $dtoClass, $domainShort, $dtoShort);
        $lines[] = '    {';
        $lines[] = '        if ($value === null) {';
        $lines[] = '            return null;';
        $lines[] = '        }';
        $lines[] = sprintf('        return new %s(', $dtoShort);
        $props = [];
        foreach ($dtoSpec['properties'] as $property) {
            $props[] = sprintf('            %s: $value->%s', $property['name'], $property['name']);
        }
        $lines[] = implode(",\n", $props);
        $lines[] = '        );';
        $lines[] = '    }';
        $lines[] = '';
        $lines[] = sprintf('    private function from%s(?%s $value): ?%s', $dtoClass, $dtoShort, $domainShort);
        $lines[] = '    {';
        $lines[] = '        if ($value === null) {';
        $lines[] = '            return null;';
        $lines[] = '        }';
        $lines[] = sprintf('        return new %s(', $domainShort);
        $props = [];
        foreach ($dtoSpec['properties'] as $property) {
            $props[] = sprintf('            %s: $value->%s', $property['name'], $property['name']);
        }
        $lines[] = implode(",\n", $props);
        $lines[] = '        );';
        $lines[] = '    }';

        return implode("\n", $lines);
    }

    /**
     * Render manifest PHP file content.
     *
     * @param array<string, mixed> $data Manifest data.
     *
     * @return string
     */
    private function renderManifest(array $data): string
    {
        $lines = [];
        $lines[] = '<?php';
        $lines[] = '';
        $lines[] = 'declare(strict_types=1);';
        $lines[] = '';
        $lines[] = 'return ' . $this->renderArray($data, 0) . ';';
        $lines[] = '';

        return implode("\n", $lines);
    }

    /**
     * Render a method signature for contract or adapter.
     *
     * @param array<string, mixed>      $signature Signature data.
     * @param bool                      $interface True when generating interface.
     * @param array<string, mixed>|null $target    Fix target.
     *
     * @return string
     */
    private function renderMethodSignature(array $signature, bool $interface, ?array $target = null): string
    {
        $params = [];
        foreach ($signature['params'] as $param) {
            $type = $param['contractType'] ?? $param['type'];
            $typeString = $type ? $this->formatType($type) : null;
            $paramString = ($typeString ? $typeString . ' ' : '') . '$' . $param['name'];
            $params[] = $paramString;
        }
        $returnType = $interface ? $signature['contractReturnType'] : $signature['contractReturnType'];
        $returnTypeString = $returnType ? $this->formatType($returnType) : null;
        $returnSegment = $returnTypeString ? ': ' . $returnTypeString : '';

        if ($interface) {
            return sprintf('    public function %s(%s)%s;', $signature['name'], implode(', ', $params), $returnSegment);
        }

        $lines = [];
        $lines[] = sprintf('    public function %s(%s)%s', $signature['name'], implode(', ', $params), $returnSegment);
        $lines[] = '    {';

        $callArgs = [];
        foreach ($signature['params'] as $param) {
            $callArgs[] = '$' . $param['name'];
        }

        $call = sprintf('$this->provider->%s(%s)', $signature['name'], implode(', ', $callArgs));
        if ($signature['returnDto'] !== null) {
            $mapper = $signature['returnDto']['className'];
            $lines[] = sprintf('        $result = %s;', $call);
            $lines[] = sprintf('        return $this->to%s($result);', $mapper);
        } else {
            if ($returnTypeString === null || $returnTypeString === 'void') {
                $lines[] = '        ' . $call . ';';
            } else {
                $lines[] = sprintf('        return %s;', $call);
            }
        }

        $lines[] = '    }';

        return implode("\n", $lines);
    }

    /**
     * Render NoOp provider source.
     *
     * @param array<string, mixed> $target Fix target.
     *
     * @return string
     */
    private function renderNoOp(array $target): string
    {
        $providerAppPascal = basename($target['providerApp']->path);
        $namespace = sprintf('App\\Components\\Bridge%s\\NoOp', $providerAppPascal);
        $contractFqcn = $target['contractFqcn'];
        $className = $target['contractName'] . 'NoOp';

        $lines = [];
        $lines[] = '<?php';
        $lines[] = '';
        $lines[] = 'declare(strict_types=1);';
        $lines[] = '';
        $lines[] = 'namespace ' . $namespace . ';';
        $lines[] = '';
        $lines[] = 'use DateTimeImmutable;';
        $lines[] = 'use DateTimeInterface;';
        $lines[] = 'use ' . FabryqProvider::class . ';';
        $lines[] = 'use ' . $contractFqcn . ';';
        foreach ($target['dtoPlan'] as $dtoSpec) {
            $lines[] = 'use ' . $dtoSpec['fqcn'] . ';';
        }
        $lines[] = '';
        $lines[] = sprintf(
            '#[FabryqProvider(capability: \'%s\', contract: %s::class, priority: -1000)]',
            $target['capability'],
            $target['contractName']
        );
        $lines[] = 'final class ' . $className . ' implements ' . $target['contractName'];
        $lines[] = '{';

        foreach ($target['signatures'] as $signature) {
            $lines[] = $this->renderNoOpMethod($signature);
        }

        $lines[] = '}';
        $lines[] = '';

        return implode("\n", $lines);
    }

    /**
     * Render NoOp method implementation.
     *
     * @param array<string, mixed> $signature Signature data.
     *
     * @return string
     */
    private function renderNoOpMethod(array $signature): string
    {
        $params = [];
        foreach ($signature['params'] as $param) {
            $type = $param['contractType'] ?? $param['type'];
            $typeString = $type ? $this->formatType($type) : null;
            $params[] = ($typeString ? $typeString . ' ' : '') . '$' . $param['name'];
        }
        $returnType = $signature['contractReturnType'];
        $returnTypeString = $returnType ? $this->formatType($returnType) : null;
        $returnSegment = $returnTypeString ? ': ' . $returnTypeString : '';

        $lines = [];
        $lines[] = sprintf('    public function %s(%s)%s', $signature['name'], implode(', ', $params), $returnSegment);
        $lines[] = '    {';

        $default = $this->defaultReturn($returnType);
        if ($default !== null) {
            $lines[] = '        return ' . $default . ';';
        }
        $lines[] = '    }';

        return implode("\n", $lines);
    }

    /**
     * Render plan Markdown output.
     *
     * @param string                           $mode      Fix mode.
     * @param FixSelection                     $selection Selection payload.
     * @param array<int, array<string, mixed>> $items     Plan items.
     * @param int                              $blockers  Blocker count.
     * @param int                              $warnings  Warning count.
     *
     * @return string Markdown content.
     */
    private function renderPlan(string $mode, FixSelection $selection, array $items, int $blockers, int $warnings): string
    {
        $lines = [];
        $lines[] = '# Fabryq Fix Plan';
        $lines[] = '';
        $lines[] = 'Fixer: crossing';
        $lines[] = 'Mode: ' . $mode;
        $lines[] = 'Selection: ' . json_encode($selection->toArray(), JSON_UNESCAPED_SLASHES);
        $lines[] = '';

        $lines[] = '## Fixable';
        $lines[] = '';
        $hasFixable = false;
        foreach ($items as $item) {
            if (!$item['fixable']) {
                continue;
            }
            $hasFixable = true;
            $ruleKey = $item['ruleKey'] ?? 'crossing';
            $lines[] = sprintf('- [%s] (%s) %s', $item['id'], $ruleKey, $item['summary']);
        }
        if (!$hasFixable) {
            $lines[] = 'None.';
        }
        $lines[] = '';

        $lines[] = '## Blockers';
        $lines[] = '';
        $hasBlockers = false;
        foreach ($items as $item) {
            if ($item['fixable']) {
                continue;
            }
            $hasBlockers = true;
            $lines[] = sprintf('- [%s] %s', $item['id'], $item['reason']);
        }
        if (!$hasBlockers) {
            $lines[] = 'None.';
        }
        $lines[] = '';

        $lines[] = sprintf('Blockers: %d', $blockers);
        $lines[] = sprintf('Warnings: %d', $warnings);
        $lines[] = '';

        return implode("\n", $lines);
    }

    /**
     * Replace new expressions marked for replacement.
     *
     * @param array<int, Node\Stmt> $stmts Statements.
     */
    private function replaceNewExpressions(array $stmts): array
    {
        $traverser = new NodeTraverser();
        $traverser->addVisitor(
            new class extends NodeVisitorAbstract {
                public function leaveNode(Node $node): ?Node
                {
                    if (!$node instanceof Node\Expr\New_) {
                        return null;
                    }
                    $replacement = $node->getAttribute('replacement');
                    if ($replacement instanceof Node\Expr) {
                        return $replacement;
                    }

                    return null;
                }
            }
        );

        return $traverser->traverse($stmts);
    }

    /**
     * Resolve a file path relative to project dir.
     *
     * @param string|null $path Path from finding.
     *
     * @return string|null Absolute file path.
     */
    private function resolveAbsolutePath(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        $normalized = str_replace('\\', '/', $path);
        if (str_starts_with($normalized, $this->projectDir . '/')) {
            return $normalized;
        }

        return $this->projectDir . '/' . ltrim($normalized, '/');
    }

    /**
     * Resolve app definition from a consumer file path.
     *
     * @param string $path Absolute file path.
     *
     * @return AppDefinition|null
     */
    private function resolveAppFromFile(string $path): ?AppDefinition
    {
        $relative = $this->normalizePath($path);
        if (!str_starts_with($relative, 'src/Apps/')) {
            return null;
        }
        $parts = explode('/', $relative);
        $appFolder = $parts[2] ?? null;
        if ($appFolder === null) {
            return null;
        }

        return $this->findAppByFolder($appFolder);
    }

    /**
     * Resolve the file path for a class.
     *
     * @param string $fqcn Fully qualified class name.
     *
     * @return string|null File path or null.
     */
    private function resolveClassFile(string $fqcn): ?string
    {
        $normalized = str_replace('\\', '/', $fqcn);
        if (!str_starts_with($normalized, 'App/')) {
            return null;
        }
        $relative = substr($normalized, 4);
        $parts = explode('/', $relative);
        $appFolder = $parts[0] ?? null;
        if ($appFolder === null || $appFolder === 'Components') {
            return null;
        }
        $relativePath = implode('/', array_slice($parts, 1));
        $path = $this->projectDir . '/src/Apps/' . $appFolder . '/' . $relativePath . '.php';

        return is_file($path) ? $path : null;
    }

    /**
     * Resolve DTO spec for a domain class.
     *
     * @param string                              $fqcn              Domain class.
     * @param string                              $providerAppPascal Provider app folder.
     * @param array<int, array<string, mixed>>    $dtoPlan           DTO plan output.
     * @param array<string, array<string, mixed>> $dtoIndex          DTO cache.
     * @param string|null                         $reason            Failure reason.
     *
     * @return array<string, mixed>|null
     */
    private function resolveDtoSpec(string $fqcn, string $providerAppPascal, array &$dtoPlan, array &$dtoIndex, ?string &$reason): ?array
    {
        if (!str_starts_with($fqcn, 'App\\' . $providerAppPascal . '\\')) {
            $reason = 'DTOs are only generated for provider app classes.';
            return null;
        }

        if (isset($dtoIndex[$fqcn])) {
            return $dtoIndex[$fqcn];
        }

        $classFile = $this->resolveClassFile($fqcn);
        if ($classFile === null) {
            $reason = 'DTO source class not found.';
            return null;
        }

        $code = file_get_contents($classFile);
        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        if ($ast === null) {
            $reason = 'DTO source class parse failed.';
            return null;
        }

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $ast = $traverser->traverse($ast);

        $nodeFinder = new NodeFinder();
        $classNode = $nodeFinder->findFirstInstanceOf($ast, Node\Stmt\Class_::class);
        if (!$classNode instanceof Node\Stmt\Class_) {
            $reason = 'DTO source class missing.';
            return null;
        }

        if ($this->hasDoctrineMarkers($classNode)) {
            $reason = 'DTO source class uses Doctrine annotations.';
            return null;
        }

        $properties = [];
        foreach ($classNode->getProperties() as $property) {
            if (!$property->isPublic()) {
                $reason = 'DTO source properties must be public.';
                return null;
            }
            if ($this->hasDoctrineMarkers($property)) {
                $reason = 'DTO source property uses Doctrine annotations.';
                return null;
            }
            $type = $this->resolveTypeDescriptor($property->type);
            if ($type === null) {
                $reason = 'DTO source property types must be declared.';
                return null;
            }
            if (!$type['isBuiltin'] && !in_array($type['fqcn'], ['DateTimeInterface', 'DateTimeImmutable'], true)) {
                $reason = 'Nested DTOs are not supported.';
                return null;
            }

            foreach ($property->props as $prop) {
                $properties[] = [
                    'name' => $prop->name->toString(),
                    'type' => $type,
                ];
            }
        }

        $constructor = $classNode->getMethod('__construct');
        if (!$constructor instanceof Node\Stmt\ClassMethod) {
            $reason = 'DTO source class must define a constructor.';
            return null;
        }

        $paramNames = [];
        foreach ($constructor->params as $param) {
            if ($param->var instanceof Node\Expr\Variable) {
                $paramNames[] = $param->var->name;
            }
        }

        foreach ($properties as $property) {
            if (!in_array($property['name'], $paramNames, true)) {
                $reason = 'DTO source constructor must accept all properties.';
                return null;
            }
        }

        $classBase = basename(str_replace('\\', '/', $fqcn));
        $dtoClass = $classBase . 'Dto';
        $namespaceSuffix = $providerAppPascal;

        if (in_array($dtoClass, array_column($dtoPlan, 'className'), true)) {
            $dtoClass = $namespaceSuffix . $classBase . 'Dto';
        }

        $dtoFqcn = sprintf('App\\Components\\Bridge%s\\Dto\\%s', $providerAppPascal, $dtoClass);

        $spec = [
            'sourceFqcn' => $fqcn,
            'className' => $dtoClass,
            'fqcn' => $dtoFqcn,
            'properties' => $properties,
        ];

        $dtoPlan[] = $spec;
        $dtoIndex[$fqcn] = $spec;

        return $spec;
    }

    /**
     * Resolve the fix mode from input.
     *
     * @param InputInterface $input Console input.
     *
     * @return string Fix mode.
     */
    private function resolveMode(InputInterface $input): string
    {
        $dryRun = (bool)$input->getOption('dry-run');
        $apply = (bool)$input->getOption('apply');

        if ($dryRun === $apply) {
            throw new UserError('Specify exactly one of --dry-run or --apply.');
        }

        return $dryRun ? FixMode::DRY_RUN : FixMode::APPLY;
    }

    /**
     * Resolve a type descriptor from a type node.
     *
     * @param Node|null $type Node type.
     *
     * @return array<string, mixed>|null
     */
    private function resolveTypeDescriptor(?Node $type): ?array
    {
        if ($type === null) {
            return null;
        }

        $nullable = false;
        if ($type instanceof Node\NullableType) {
            $nullable = true;
            $type = $type->type;
        }

        if ($type instanceof Node\UnionType || $type instanceof Node\IntersectionType) {
            return null;
        }

        if ($type instanceof Node\Identifier) {
            $name = $type->name;
            return [
                'type' => $name,
                'fqcn' => $name,
                'isBuiltin' => true,
                'nullable' => $nullable,
            ];
        }

        if ($type instanceof Node\Name) {
            $resolved = $type->getAttribute('resolvedName');
            $name = $resolved instanceof Node\Name ? $resolved->toString() : $type->toString();
            if (in_array($name, ['self', 'static', 'parent'], true)) {
                return null;
            }
            $isBuiltin = in_array($name, ['int', 'float', 'string', 'bool', 'array', 'callable', 'iterable', 'mixed', 'void', 'object'], true);
            if ($isBuiltin) {
                return [
                    'type' => $name,
                    'fqcn' => $name,
                    'isBuiltin' => true,
                    'nullable' => $nullable,
                ];
            }

            return [
                'type' => $name,
                'fqcn' => $name,
                'isBuiltin' => false,
                'nullable' => $nullable,
            ];
        }

        return null;
    }

    /**
     * Rewrite consumer file to use contract injection.
     *
     * @param array<string, mixed> $target Fix target.
     *
     * @return array<string, mixed>|null Blocker plan item.
     */
    private function rewriteConsumer(array $target): ?array
    {
        $code = file_get_contents($target['consumerFile']);

        // FIX: nikic/php-parser v5 compatibility
        $lexer = new Emulative(null);
        $parser = new \PhpParser\Parser\Php8($lexer);

        try {
            $oldStmts = $parser->parse($code);
            // FIX: getTokens() existiert nicht mehr, wir rufen tokenize() explizit auf
            $tokens = $lexer->tokenize($code);
        } catch (\Throwable $e) {
            return $this->blockedPlan($target['findingId'], $target['finding'], 'Consumer file parse failed: ' . $e->getMessage());
        }

        if ($oldStmts === null) {
            return $this->blockedPlan($target['findingId'], $target['finding'], 'Consumer file parse failed.');
        }

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new ParentConnectingVisitor());
        $traverser->addVisitor(new NameResolver());
        $stmts = $traverser->traverse($oldStmts);

        $providerFqcn = $target['providerFqcn'];
        $contractFqcn = $target['contractFqcn'];
        $contractShort = $this->shortName($contractFqcn);

        $updated = false;
        $needsInjection = false;
        $propertyName = lcfirst(str_replace('Interface', '', $contractShort));

        $nodeFinder = new NodeFinder();
        $classNode = $nodeFinder->findFirstInstanceOf($stmts, Node\Stmt\Class_::class);
        if (!$classNode instanceof Node\Stmt\Class_) {
            return $this->blockedPlan($target['findingId'], $target['finding'], 'Consumer class not found.');
        }

        // 1. Modify Use statements (in-place)
        foreach ($nodeFinder->findInstanceOf($stmts, Node\Stmt\UseUse::class) as $useUse) {
            $resolved = $useUse->name->getAttribute('resolvedName');
            $resolvedName = $resolved instanceof Node\Name ? $resolved->toString() : $useUse->name->toString();
            if ($resolvedName === $providerFqcn) {
                $useUse->name = new Node\Name($contractFqcn);
                $updated = true;
            }
        }

        // 2. Identify and mark New_ expressions (in-place)
        foreach ($nodeFinder->findInstanceOf($stmts, Node\Expr\New_::class) as $newExpr) {
            $newType = $this->resolveTypeDescriptor($newExpr->class);
            if ($newType !== null && $newType['fqcn'] === $providerFqcn) {
                $newExpr->class = new Node\Name('');
                $replacement = new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $propertyName);
                $newExpr->setAttribute('replacement', $replacement);
                $needsInjection = true;
                $updated = true;
            }
        }

        // 3. Replace TypeHint Names using a NodeTraverser (FIX for missing KIND_FQ in v5)
        $nameReplacer = new class($providerFqcn, $contractFqcn) extends NodeVisitorAbstract {
            public bool $updated = false;

            public function __construct(
                private readonly string $target,
                private readonly string $replacement
            ) {}

            public function leaveNode(Node $node)
            {
                if ($node instanceof Node\Name) {
                    $resolved = $node->getAttribute('resolvedName');
                    // Check if name matches provider FQCN
                    if ($resolved instanceof Node\Name && $resolved->toString() === $this->target) {
                        // Inline check for isTypeHintNode logic
                        $parent = $node->getAttribute('parent');
                        if ($parent instanceof Node\NullableType) {
                            $parent = $parent->getAttribute('parent');
                        }
                        $isTypeHint = $parent instanceof Node\Param
                            || $parent instanceof Node\Stmt\Property
                            || $parent instanceof Node\FunctionLike;

                        if ($isTypeHint) {
                            $this->updated = true;
                            // Replace with FullyQualified node instead of setting kind attribute
                            return new Node\Name\FullyQualified($this->replacement);
                        }
                    }
                }
                return null;
            }
        };

        $replacerTraverser = new NodeTraverser();
        $replacerTraverser->addVisitor($nameReplacer);
        $stmts = $replacerTraverser->traverse($stmts);

        if ($nameReplacer->updated) {
            $updated = true;
        }

        if (!$updated) {
            return null;
        }

        if ($needsInjection) {
            $blocker = $this->ensureConstructorInjection($classNode, $contractFqcn, $propertyName);
            if ($blocker !== null) {
                return $blocker;
            }
        }

        $stmts = $this->replaceNewExpressions($stmts);
        $stmts = $this->pruneImports($stmts);
        $printer = new Standard();
        // FIX: Verwende $tokens Variable statt $lexer->getTokens()
        $newCode = $printer->printFormatPreserving($stmts, $oldStmts, $tokens);
        $this->filesystem->dumpFile($target['consumerFile'], $newCode);

        return null;
    }

    /**
     * Short class name from FQCN.
     *
     * @param string $fqcn Fully qualified class name.
     *
     * @return string
     */
    private function shortName(string $fqcn): string
    {
        return basename(str_replace('\\', '/', $fqcn));
    }

    /**
     * Update consumer manifest with consumes entry.
     *
     * @param string               $manifestPath Manifest path.
     * @param array<string, mixed> $target       Fix target.
     *
     * @return array<string, mixed>|null
     */
    private function updateManifestConsumes(string $manifestPath, array $target): ?array
    {
        $data = $this->loadManifest($manifestPath);
        if ($data === null) {
            return $this->blockedPlan($target['findingId'], $target['finding'], 'Consumer manifest invalid.');
        }

        $consumes = $data['consumes'] ?? [];
        foreach ($consumes as &$entry) {
            if (is_string($entry) && $entry === $target['capability']) {
                $entry = [
                    'capabilityId' => $target['capability'],
                    'required' => true,
                    'contract' => $target['contractFqcn'],
                ];
                $data['consumes'] = $consumes;
                return $this->writeManifest($manifestPath, $data, $target);
            }
            if (is_array($entry) && ($entry['capabilityId'] ?? null) === $target['capability']) {
                if (isset($entry['contract']) && $entry['contract'] !== $target['contractFqcn']) {
                    return $this->blockedPlan($target['findingId'], $target['finding'], 'Consumer manifest consumes incompatible contract.');
                }
                $entry['contract'] = $target['contractFqcn'];
                $data['consumes'] = $consumes;
                return $this->writeManifest($manifestPath, $data, $target);
            }
        }
        unset($entry);

        $consumes[] = [
            'capabilityId' => $target['capability'],
            'required' => true,
            'contract' => $target['contractFqcn'],
        ];
        $data['consumes'] = $consumes;

        return $this->writeManifest($manifestPath, $data, $target);
    }

    /**
     * Update provider manifest with provides entry.
     *
     * @param string               $manifestPath Manifest path.
     * @param array<string, mixed> $target       Fix target.
     *
     * @return array<string, mixed>|null
     */
    private function updateManifestProvides(string $manifestPath, array $target): ?array
    {
        $data = $this->loadManifest($manifestPath);
        if ($data === null) {
            return $this->blockedPlan($target['findingId'], $target['finding'], 'Provider manifest invalid.');
        }

        $provides = $data['provides'] ?? [];
        foreach ($provides as &$entry) {
            if (is_string($entry) && $entry === $target['capability']) {
                $entry = [
                    'capabilityId' => $target['capability'],
                    'contract' => $target['contractFqcn'],
                ];
                $data['provides'] = $provides;
                return $this->writeManifest($manifestPath, $data, $target);
            }
            if (is_array($entry) && ($entry['capabilityId'] ?? null) === $target['capability']) {
                if (($entry['contract'] ?? null) !== $target['contractFqcn']) {
                    return $this->blockedPlan($target['findingId'], $target['finding'], 'Provider manifest provides incompatible contract.');
                }
                return $this->writeManifest($manifestPath, $data, $target);
            }
        }
        unset($entry);

        $provides[] = [
            'capabilityId' => $target['capability'],
            'contract' => $target['contractFqcn'],
        ];
        $data['provides'] = $provides;

        return $this->writeManifest($manifestPath, $data, $target);
    }

    /**
     * Write file if compatible, else return blocker.
     *
     * @param string               $path    File path.
     * @param string               $content Expected content.
     * @param array<string, mixed> $target  Fix target.
     *
     * @return array<string, mixed>|null
     */
    private function writeFileIfCompatible(string $path, string $content, array $target): ?array
    {
        if ($this->filesystem->exists($path)) {
            $existing = (string)file_get_contents($path);
            if (trim($existing) !== trim($content)) {
                return $this->blockedPlan($target['findingId'], $target['finding'], sprintf('File %s already exists with different content.', $this->normalizePath($path)));
            }
        }

        $this->filesystem->dumpFile($path, $content);

        return null;
    }

    /**
     * Write manifest data to file.
     *
     * @param string               $manifestPath Manifest path.
     * @param array<string, mixed> $data         Manifest data.
     * @param array<string, mixed> $target       Fix target.
     *
     * @return array<string, mixed>|null
     */
    private function writeManifest(string $manifestPath, array $data, array $target): ?array
    {
        $content = $this->renderManifest($data);
        $this->filesystem->dumpFile($manifestPath, $content);

        return null;
    }

    /**
     * Apply entity-to-interface replacements in a consumer file.
     *
     * @param array<string, mixed> $target
     * @param array<int, string>   $changedFiles
     * @param array<int, array<string, string>> $createdInterfaces
     *
     * @return array<string, mixed>|null
     */
    private function applyEntityToInterface(array $target, array &$changedFiles, array &$createdInterfaces): ?array
    {
        $consumerFile = $target['consumerFile'];
        $entityFqcn = $target['entityFqcn'];
        $entityShort = $target['entityShort'];
        $interfaceFqcn = $target['interfaceFqcn'];
        $interfacePath = $target['interfacePath'];

        $code = file_get_contents($consumerFile);

        $lexer = new Emulative(null);
        $parser = new \PhpParser\Parser\Php8($lexer);

        try {
            $oldStmts = $parser->parse($code);
            $tokens = $lexer->tokenize($code);
        } catch (\Throwable $e) {
            return $this->blockedPlan($target['findingId'], $target['finding'], 'Consumer file parse failed: ' . $e->getMessage());
        }

        if ($oldStmts === null) {
            return $this->blockedPlan($target['findingId'], $target['finding'], 'Consumer file parse failed.');
        }

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new ParentConnectingVisitor());
        $traverser->addVisitor(new NameResolver());
        $stmts = $traverser->traverse($oldStmts);

        $updated = false;

        $typeReplacer = new class($entityFqcn, $interfaceFqcn, $updated) extends NodeVisitorAbstract {
            private bool $updated;

            public function __construct(
                private readonly string $entityFqcn,
                private readonly string $interfaceFqcn,
                bool &$updated
            ) {
                $this->updated = &$updated;
            }

            public function enterNode(Node $node): ?Node
            {
                if ($node instanceof Node\Param) {
                    $node->type = $this->replaceType($node->type);
                }
                if ($node instanceof Node\Stmt\Property) {
                    $node->type = $this->replaceType($node->type);
                }
                if ($node instanceof Node\FunctionLike) {
                    $node->returnType = $this->replaceType($node->getReturnType());
                }

                return null;
            }

            private function replaceType(?Node $type): ?Node
            {
                if ($type === null) {
                    return null;
                }

                if ($type instanceof Node\NullableType) {
                    $type->type = $this->replaceType($type->type);
                    return $type;
                }

                if ($type instanceof Node\UnionType || $type instanceof Node\IntersectionType) {
                    foreach ($type->types as $index => $subType) {
                        $type->types[$index] = $this->replaceType($subType);
                    }
                    return $type;
                }

                if ($type instanceof Node\Name) {
                    $resolved = $type->getAttribute('resolvedName');
                    $fqcn = $resolved instanceof Node\Name ? $resolved->toString() : $type->toString();
                    if ($fqcn === $this->entityFqcn) {
                        $this->updated = true;
                        return new Node\Name\FullyQualified($this->interfaceFqcn);
                    }
                }

                return $type;
            }
        };

        $docReplacer = new class($entityFqcn, $interfaceFqcn, $entityShort, $updated) extends NodeVisitorAbstract {
            private bool $updated;

            public function __construct(
                private readonly string $entityFqcn,
                private readonly string $interfaceFqcn,
                private readonly string $entityShort,
                bool &$updated
            ) {
                $this->updated = &$updated;
            }

            public function enterNode(Node $node): ?Node
            {
                $doc = $node->getDocComment();
                if ($doc === null) {
                    return null;
                }

                $text = $doc->getText();
                $updatedText = $this->replaceDocTypes($text);
                if ($updatedText !== $text) {
                    $node->setDocComment(new Doc($updatedText));
                    $this->updated = true;
                }

                return null;
            }

            private function replaceDocTypes(string $text): string
            {
                $interfaceFqcn = '\\' . $this->interfaceFqcn;
                return preg_replace_callback('/@(param|return|var)\s+([^\s]+)(.*)/', function (array $matches) use ($interfaceFqcn): string {
                    $types = explode('|', $matches[2]);
                    foreach ($types as $index => $type) {
                        $types[$index] = $this->replaceDocType($type, $interfaceFqcn);
                    }
                    return '@' . $matches[1] . ' ' . implode('|', $types) . $matches[3];
                }, $text) ?? $text;
            }

            private function replaceDocType(string $type, string $interfaceFqcn): string
            {
                $nullable = str_starts_with($type, '?') ? '?' : '';
                if ($nullable !== '') {
                    $type = substr($type, 1);
                }

                $suffix = '';
                if (str_ends_with($type, '[]')) {
                    $suffix = '[]';
                    $type = substr($type, 0, -2);
                }

                $normalized = ltrim($type, '\\');
                if ($normalized === $this->entityFqcn || $type === $this->entityShort) {
                    $type = $interfaceFqcn;
                }

                return $nullable . $type . $suffix;
            }
        };

        $traverser = new NodeTraverser();
        $traverser->addVisitor($typeReplacer);
        $traverser->addVisitor($docReplacer);
        $stmts = $traverser->traverse($stmts);

        if ($updated && !$this->filesystem->exists($interfacePath)) {
            $interfaceDir = dirname($interfacePath);
            $this->filesystem->mkdir($interfaceDir);
            $this->filesystem->dumpFile($interfacePath, $this->renderEntityInterface($target['providerApp'], $interfaceFqcn));
            $createdInterfaces[] = [
                'path' => $this->normalizePath($interfacePath),
                'fqcn' => $interfaceFqcn,
                'ruleKey' => self::ENTITY_TO_INTERFACE_RULE,
            ];
            $changedFiles[] = $this->normalizePath($interfacePath);
        }

        $stmts = $this->pruneImports($stmts);
        $printer = new Standard();
        $newCode = $printer->printFormatPreserving($stmts, $oldStmts, $tokens);
        if ($newCode !== $code) {
            $this->filesystem->dumpFile($consumerFile, $newCode);
            $changedFiles[] = $this->normalizePath($consumerFile);
        }

        return null;
    }

    /**
     * Parse entity FQCN into base namespace and entity name.
     *
     * @param string $fqcn
     *
     * @return array{appFolder: string, baseNamespace: string, entity: string}|null
     */
    private function parseEntityFqcn(string $fqcn): ?array
    {
        if (!str_starts_with($fqcn, 'App\\')) {
            return null;
        }

        $parts = explode('\\', $fqcn);
        $entityIndex = array_search('Entity', $parts, true);
        if ($entityIndex === false || $entityIndex >= count($parts) - 1) {
            return null;
        }

        $baseParts = array_slice($parts, 0, $entityIndex);
        if (count($baseParts) < 2) {
            return null;
        }

        $appFolder = $baseParts[1];
        if ($appFolder === 'Components') {
            return null;
        }

        return [
            'appFolder' => $appFolder,
            'baseNamespace' => implode('\\', $baseParts),
            'entity' => $parts[$entityIndex + 1],
        ];
    }

    /**
     * Initialize the import pruner based on input flags.
     *
     * @param InputInterface $input Console input.
     */
    private function initImportPruner(InputInterface $input): void
    {
        $this->pruneUnresolvableImports = (bool) $input->getOption('prune-unresolvable-imports');
        $autoloadAvailable = is_file($this->projectDir . '/vendor/autoload.php');

        if ($this->pruneUnresolvableImports && $autoloadAvailable) {
            require_once $this->projectDir . '/vendor/autoload.php';
        }

        $this->importPruner = new ImportPruner($autoloadAvailable);
    }

    /**
     * Apply import pruning to updated statements.
     *
     * @param array<int, Node\Stmt> $stmts
     *
     * @return array<int, Node\Stmt>
     */
    private function pruneImports(array $stmts): array
    {
        if ($this->importPruner === null) {
            $this->importPruner = new ImportPruner(false);
        }

        return $this->importPruner->prune($stmts, $this->pruneUnresolvableImports);
    }
}
    /**
     * Build a class file path for the given FQCN.
     *
     * @param string $fqcn
     * @param string $appFolder
     *
     * @return string
     */
    private function buildClassPath(string $fqcn, string $appFolder): string
    {
        $normalized = str_replace('\\', '/', $fqcn);
        $relative = str_starts_with($normalized, 'App/') ? substr($normalized, 4) : $normalized;
        $parts = explode('/', $relative);
        $relativePath = implode('/', array_slice($parts, 1));

        return $this->projectDir . '/src/Apps/' . $appFolder . '/' . $relativePath . '.php';
    }

    /**
     * Render a contracts interface for an entity replacement.
     *
     * @param AppDefinition $providerApp
     * @param string        $interfaceFqcn
     *
     * @return string
     */
    private function renderEntityInterface(AppDefinition $providerApp, string $interfaceFqcn): string
    {
        $namespace = dirname(str_replace('\\', '/', $interfaceFqcn));
        $namespace = str_replace('/', '\\', $namespace);
        $interfaceName = $this->shortName($interfaceFqcn);

        return "<?php\n\n".
            "declare(strict_types=1);\n\n".
            "namespace ".$namespace.";\n\n".
            "/**\n".
            " * Contracts interface for ".$providerApp->manifest->appId." entity ".$interfaceName.".\n".
            " */\n".
            "interface ".$interfaceName."\n".
            "{\n".
            "    // TODO: define shared entity contract\n".
            "}\n";
    }
