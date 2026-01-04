<?php

/**
 * Analyzer that scans for references to a namespace prefix.
 *
 * @package   Fabryq\Cli\Analyzer
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Analyzer;

use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitor\ParentConnectingVisitor;
use PhpParser\ParserFactory;
use Symfony\Component\Finder\Finder;

/**
 * Scans PHP files for references to a specific namespace prefix.
 */
final class ReferenceScanner
{
    /**
     * Find references to the given namespace prefix.
     *
     * @param string $projectDir  Absolute project directory.
     * @param string $prefix      Namespace prefix (FQCN).
     * @param string $excludePath Absolute path to exclude from scanning.
     *
     * @return array<int, array{file: string, line: int, symbol: string}>
     */
    public function findReferences(string $projectDir, string $prefix, string $excludePath): array
    {
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
        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $nodeFinder = new NodeFinder();
        $references = [];

        foreach ($finder as $file) {
            $path = $file->getPathname();
            if (str_starts_with($path, rtrim($excludePath, '/') . '/')) {
                continue;
            }

            $code = $file->getContents();
            try {
                $ast = $parser->parse($code);
            } catch (\Throwable) {
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
                if ($nameNode->getAttribute('parent') instanceof Node\Stmt\Namespace_) {
                    continue;
                }

                $resolved = $nameNode->getAttribute('resolvedName');
                if (!$resolved instanceof Node\Name) {
                    $parent = $nameNode->getAttribute('parent');
                    if ($parent instanceof Node\Stmt\UseUse || $parent instanceof Node\Stmt\GroupUse) {
                        $resolved = $nameNode;
                    } else {
                        continue;
                    }
                }

                $fqcn = $resolved->toString();
                if (!str_starts_with($fqcn, $prefix)) {
                    continue;
                }

                $references[] = [
                    'file' => $path,
                    'line' => $nameNode->getLine(),
                    'symbol' => $fqcn,
                ];
            }
        }

        return $references;
    }
}
