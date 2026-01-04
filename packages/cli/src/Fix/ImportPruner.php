<?php

/**
 * Import pruning helper for PHP ASTs.
 *
 * @package   Fabryq\Cli\Fix
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Fix;

use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitor\ParentConnectingVisitor;
use PhpParser\NodeVisitorAbstract;

/**
 * Removes unused and optionally unresolvable imports.
 */
final class ImportPruner
{
    /**
     * @param bool $autoloadAvailable Whether composer autoload is available.
     */
    public function __construct(
        private readonly bool $autoloadAvailable,
    ) {
    }

    /**
     * Remove unused imports and optionally unresolvable imports.
     *
     * @param array<int, Node\Stmt> $stmts AST statements.
     * @param bool                  $pruneUnresolvable Whether to remove unresolvable imports.
     *
     * @return array<int, Node\Stmt>
     */
    public function prune(array $stmts, bool $pruneUnresolvable): array
    {
        $stmts = $this->resolveNames($stmts);
        $used = $this->collectUsedFqcns($stmts);

        $stmts = $this->removeImports($stmts, static fn (string $fqcn): bool => !isset($used[$fqcn]));

        if ($pruneUnresolvable && $this->autoloadAvailable) {
            $stmts = $this->removeImports($stmts, static function (string $fqcn): bool {
                if (class_exists($fqcn) || interface_exists($fqcn) || trait_exists($fqcn)) {
                    return false;
                }
                if (function_exists('enum_exists') && enum_exists($fqcn)) {
                    return false;
                }
                return true;
            });
        }

        return $stmts;
    }

    /**
     * @param array<int, Node\Stmt> $stmts
     *
     * @return array<int, Node\Stmt>
     */
    private function resolveNames(array $stmts): array
    {
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new ParentConnectingVisitor());
        $traverser->addVisitor(new NameResolver());

        return $traverser->traverse($stmts);
    }

    /**
     * Collect fully-qualified class names referenced in code.
     *
     * @param array<int, Node\Stmt> $stmts
     *
     * @return array<string, bool>
     */
    private function collectUsedFqcns(array $stmts): array
    {
        $used = [];
        $nodeFinder = new NodeFinder();

        foreach ($nodeFinder->findInstanceOf($stmts, Node\Name::class) as $nameNode) {
            $parent = $nameNode->getAttribute('parent');
            if ($parent instanceof Node\Stmt\UseUse || $parent instanceof Node\Stmt\GroupUse || $parent instanceof Node\Stmt\Namespace_) {
                continue;
            }

            $resolved = $nameNode->getAttribute('resolvedName');
            $usedName = $resolved instanceof Node\Name ? $resolved->toString() : $nameNode->toString();
            $used[$usedName] = true;
        }

        return $used;
    }

    /**
     * Remove imports matching the predicate.
     *
     * @param array<int, Node\Stmt> $stmts
     * @param callable              $shouldRemove
     *
     * @return array<int, Node\Stmt>
     */
    private function removeImports(array $stmts, callable $shouldRemove): array
    {
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new class ($shouldRemove) extends NodeVisitorAbstract {
            private readonly \Closure $shouldRemove;

            public function __construct(callable $shouldRemove)
            {
                $this->shouldRemove = \Closure::fromCallable($shouldRemove);
            }

            public function leaveNode(Node $node)
            {
                if ($node instanceof Node\Stmt\Use_) {
                    $node->uses = array_values(array_filter($node->uses, function (Node\Stmt\UseUse $useUse): bool {
                        $resolved = $useUse->name->getAttribute('resolvedName');
                        $fqcn = $resolved instanceof Node\Name ? $resolved->toString() : $useUse->name->toString();
                        return !($this->shouldRemove)($fqcn);
                    }));

                    if ($node->uses === []) {
                        return NodeTraverser::REMOVE_NODE;
                    }
                }

                if ($node instanceof Node\Stmt\GroupUse) {
                    $node->uses = array_values(array_filter($node->uses, function (Node\Stmt\UseUse $useUse) use ($node): bool {
                        $prefix = $node->prefix->toString();
                        $fqcn = $prefix . '\\' . $useUse->name->toString();
                        $resolved = $useUse->name->getAttribute('resolvedName');
                        $resolvedName = $resolved instanceof Node\Name ? $resolved->toString() : $fqcn;
                        return !($this->shouldRemove)($resolvedName);
                    }));

                    if ($node->uses === []) {
                        return NodeTraverser::REMOVE_NODE;
                    }
                }

                return null;
            }
        });

        return $traverser->traverse($stmts);
    }
}
