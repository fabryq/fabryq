<?php

/**
 * Analyzer that detects forbidden service locator usage.
 *
 * @package Fabryq\Cli\Analyzer
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Analyzer;

use Fabryq\Cli\Report\Finding;
use Fabryq\Cli\Report\FindingLocation;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitor\ParentConnectingVisitor;
use PhpParser\ParserFactory;
use Symfony\Component\Finder\Finder;

/**
 * Scans app and component code for service locator patterns.
 */
final class ServiceLocatorScanner
{
    private const FORBIDDEN_FQCNS = [
        'Psr\\Container\\ContainerInterface',
        'Symfony\\Component\\DependencyInjection\\ContainerInterface',
        'Symfony\\Contracts\\Service\\ServiceProviderInterface',
        'Symfony\\Component\\DependencyInjection\\ServiceLocator',
    ];

    /**
     * Scan the project for forbidden service locator usage.
     *
     * @param string $projectDir Absolute project directory.
     *
     * @return Finding[]
     */
    public function scan(string $projectDir): array
    {
        $appsDir = $projectDir . '/src/Apps';
        $componentsDir = $projectDir . '/src/Components';
        $paths = [];

        if (is_dir($appsDir)) {
            $paths[] = $appsDir;
        }

        if (is_dir($componentsDir)) {
            $paths[] = $componentsDir;
        }

        if ($paths === []) {
            return [];
        }

        $finder = new Finder();
        $finder->files()->in($paths)->name('*.php');

        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $nodeFinder = new NodeFinder();
        $findings = [];

        foreach ($finder as $file) {
            $path = $file->getPathname();
            $code = $file->getContents();

            try {
                $ast = $parser->parse($code);
            } catch (\Throwable $exception) {
                continue;
            }

            if ($ast === null) {
                continue;
            }

            $traverser = new NodeTraverser();
            $traverser->addVisitor(new ParentConnectingVisitor());
            $traverser->addVisitor(new NameResolver());
            $ast = $traverser->traverse($ast);

            foreach ($nodeFinder->findInstanceOf($ast, Node\Name::class) as $nameNode) {
                $resolved = $nameNode->getAttribute('resolvedName');
                $fqcn = $resolved instanceof Node\Name ? $resolved->toString() : $nameNode->toString();
                if (!in_array($fqcn, self::FORBIDDEN_FQCNS, true)) {
                    continue;
                }
                if (!$this->isTypeHint($nameNode)) {
                    continue;
                }

                $findings[] = new Finding(
                    'FABRYQ.RUNTIME.SERVICE_LOCATOR_FORBIDDEN',
                    'BLOCKER',
                    sprintf('Service locator type "%s" is forbidden.', $fqcn),
                    new FindingLocation($path, $nameNode->getLine(), $fqcn),
                    ['primary' => 'typehint|' . $fqcn],
                    'Inject FabryqContext or explicit dependencies instead of containers.'
                );
            }

            foreach ($nodeFinder->findInstanceOf($ast, Node\Expr\MethodCall::class) as $call) {
                if (!$call->name instanceof Node\Identifier || $call->name->toString() !== 'get') {
                    continue;
                }
                if (!$this->isContainerAccess($call->var)) {
                    continue;
                }

                $findings[] = new Finding(
                    'FABRYQ.RUNTIME.SERVICE_LOCATOR_FORBIDDEN',
                    'BLOCKER',
                    'Service locator access via container->get() is forbidden.',
                    new FindingLocation($path, $call->getLine(), 'container->get'),
                    ['primary' => 'method-call|get'],
                    'Inject FabryqContext or explicit dependencies instead of containers.'
                );
            }

            foreach ($nodeFinder->findInstanceOf($ast, Node\Expr\StaticCall::class) as $call) {
                if (!$call->name instanceof Node\Identifier || $call->name->toString() !== 'getContainer') {
                    continue;
                }

                $findings[] = new Finding(
                    'FABRYQ.RUNTIME.SERVICE_LOCATOR_FORBIDDEN',
                    'BLOCKER',
                    'Static getContainer() usage is forbidden.',
                    new FindingLocation($path, $call->getLine(), 'static::getContainer'),
                    ['primary' => 'static-call|getContainer'],
                    'Inject FabryqContext or explicit dependencies instead of containers.'
                );
            }
        }

        return $findings;
    }

    /**
     * Check if the node is used as a type hint.
     *
     * @param Node\Name $nameNode Name node.
     *
     * @return bool
     */
    private function isTypeHint(Node\Name $nameNode): bool
    {
        $node = $nameNode;
        $parent = $node->getAttribute('parent');

        if ($parent instanceof Node\NullableType || $parent instanceof Node\UnionType || $parent instanceof Node\IntersectionType) {
            $node = $parent;
            $parent = $node->getAttribute('parent');
        }

        if ($parent instanceof Node\Param) {
            return $parent->type === $node;
        }

        if ($parent instanceof Node\Stmt\Property) {
            return $parent->type === $node;
        }

        if ($parent instanceof Node\FunctionLike) {
            return $parent->getReturnType() === $node;
        }

        return false;
    }

    /**
     * Check whether a node represents container access.
     *
     * @param Node\Expr $expr Expression node.
     *
     * @return bool
     */
    private function isContainerAccess(Node\Expr $expr): bool
    {
        if ($expr instanceof Node\Expr\Variable) {
            return $expr->name === 'container';
        }

        if ($expr instanceof Node\Expr\PropertyFetch) {
            if ($expr->var instanceof Node\Expr\Variable && $expr->var->name === 'this') {
                if ($expr->name instanceof Node\Identifier) {
                    return $expr->name->toString() === 'container';
                }
            }
        }

        return false;
    }
}
