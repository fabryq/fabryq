<?php

/**
 * Analyzer that detects cross-app references in source code.
 *
 * @package   Fabryq\Cli\Analyzer
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Analyzer;

use Fabryq\Cli\Report\Finding;
use Fabryq\Cli\Report\FindingLocation;
use Fabryq\Cli\Report\Severity;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitor\ParentConnectingVisitor;
use PhpParser\ParserFactory;
use Symfony\Component\Finder\Finder;

/**
 * Scans for references between apps and global components.
 */
final class CrossAppReferenceScanner
{
    /**
     * Scan application and component sources for invalid references.
     *
     * Side effects:
     * - Reads PHP source files from disk.
     *
     * @param string $projectDir Absolute project directory.
     *
     * @return Finding[]
     */
    public function scan(string $projectDir): array
    {
        $findings = [];
        // Use createForNewestSupportedVersion() for modern syntax compatibility.
        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $finder = new Finder();
        $paths = [];

        $appsDir = $projectDir . '/src/Apps';
        if (is_dir($appsDir)) {
            $paths[] = $appsDir;
        }

        $componentsDir = $projectDir . '/src/Components';
        if (is_dir($componentsDir)) {
            $paths[] = $componentsDir;
        }

        if ($paths === []) {
            return [];
        }

        $finder->files()->in($paths)->name('*.php');

        $nodeFinder = new NodeFinder();

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

            $context = $this->resolveContext($projectDir, $path);
            if ($context === null) {
                continue;
            }

            foreach ($nodeFinder->findInstanceOf($ast, Node\Name::class) as $nameNode) {
                // Ignore the namespace declaration itself
                if ($nameNode->getAttribute('parent') instanceof Node\Stmt\Namespace_) {
                    continue;
                }

                $resolved = $nameNode->getAttribute('resolvedName');

                // Fallback for use statements when resolvedName is unavailable.
                // If resolvedName is missing, check whether this is an import.
                if (!$resolved instanceof Node\Name) {
                    $parent = $nameNode->getAttribute('parent');
                    if ($parent instanceof Node\Stmt\UseUse || $parent instanceof Node\Stmt\GroupUse) {
                        // In use statements, the name itself is the resolved name.
                        $resolved = $nameNode;
                    } else {
                        continue;
                    }
                }

                $fqcn = $resolved->toString();
                if (!str_starts_with($fqcn, 'App\\')) {
                    continue;
                }

                $referenceKind = $this->resolveReferenceKind($nameNode);
                $parts = explode('\\', $fqcn);
                $segment = $parts[1] ?? '';
                if ($segment === 'Components') {
                    continue;
                }

                if ($context['type'] === 'app') {
                    if ($segment === $context['app']) {
                        continue;
                    }

                    $findings[] = new Finding(
                        'FABRYQ.APP.CROSSING',
                        Severity::BLOCKER,
                        sprintf('App %s references %s.', $context['app'], $fqcn),
                        new FindingLocation($path, $nameNode->getLine(), $fqcn),
                        ['primary' => $fqcn.'|'.$referenceKind],
                        null,
                        in_array($referenceKind, ['use', 'typehint', 'new'], true),
                        in_array($referenceKind, ['use', 'typehint', 'new'], true) ? 'crossing' : null
                    );
                } else {
                    $findings[] = new Finding(
                        'FABRYQ.GLOBAL_COMPONENT.REFERENCES_APP',
                        Severity::BLOCKER,
                        sprintf('Global component references %s.', $fqcn),
                        new FindingLocation($path, $nameNode->getLine(), $fqcn),
                        ['primary' => $fqcn]
                    );
                }
            }
        }

        return $findings;
    }

    /**
     * Resolve the context (app or global) for a file path.
     *
     * @param string $projectDir Absolute project directory.
     * @param string $path       Absolute file path.
     *
     * @return array{type: 'app', app: string}|array{type: 'global'}|null
     */
    private function resolveContext(string $projectDir, string $path): ?array
    {
        $relative = ltrim(str_replace($projectDir, '', $path), '/');
        if (str_starts_with($relative, 'src/Apps/')) {
            $parts = explode('/', $relative);
            $app = $parts[2] ?? null;
            if ($app === null) {
                return null;
            }
            return ['type' => 'app', 'app' => $app];
        }

        if (str_starts_with($relative, 'src/Components/')) {
            return ['type' => 'global'];
        }

        return null;
    }

    /**
     * Resolve the reference kind for a Name node.
     *
     * @param Node\Name $nameNode Name node with parent attributes.
     *
     * @return string Reference kind label.
     */
    private function resolveReferenceKind(Node\Name $nameNode): string
    {
        $parent = $nameNode->getAttribute('parent');

        if ($parent instanceof Node\Stmt\UseUse || $parent instanceof Node\Stmt\GroupUse) {
            return 'use';
        }

        if ($parent instanceof Node\Expr\New_ && $parent->class === $nameNode) {
            return 'new';
        }

        if ($parent instanceof Node\Expr\StaticCall || $parent instanceof Node\Expr\ClassConstFetch || $parent instanceof Node\Expr\StaticPropertyFetch) {
            return 'static';
        }

        if ($parent instanceof Node\Stmt\Class_ && $parent->extends === $nameNode) {
            return 'extends';
        }

        if ($parent instanceof Node\Stmt\Class_ && in_array($nameNode, $parent->implements, true)) {
            return 'implements';
        }

        if ($parent instanceof Node\Stmt\TraitUse && in_array($nameNode, $parent->traits, true)) {
            return 'trait';
        }

        if ($parent instanceof Node\Stmt\Catch_) {
            return 'catch';
        }

        if ($parent instanceof Node\Attribute) {
            return 'attribute';
        }

        if ($parent instanceof Node\Expr\Instanceof_) {
            return 'instanceof';
        }

        if ($this->isTypeHint($nameNode)) {
            return 'typehint';
        }

        return 'reference';
    }

    /**
     * Check if a Name node is used as a type hint.
     *
     * @param Node\Name $nameNode Name node to inspect.
     *
     * @return bool True if used as a type hint.
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
}
