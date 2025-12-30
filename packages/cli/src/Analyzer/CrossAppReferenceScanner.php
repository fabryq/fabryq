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
        // KORREKTUR 1: Verwenden Sie createForNewestSupportedVersion() für Kompatibilität
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

                // KORREKTUR 2: Fallback für use-Statements
                // Wenn resolvedName fehlt, prüfen wir, ob es sich um einen Import handelt.
                if (!$resolved instanceof Node\Name) {
                    $parent = $nameNode->getAttribute('parent');
                    if ($parent instanceof Node\Stmt\UseUse || $parent instanceof Node\Stmt\GroupUse) {
                        // In use-Statements ist der Name selbst der aufgelöste Name
                        $resolved = $nameNode;
                    } else {
                        continue;
                    }
                }

                $fqcn = $resolved->toString();
                if (!str_starts_with($fqcn, 'App\\')) {
                    continue;
                }

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
                        'BLOCKER',
                        sprintf('App %s references %s.', $context['app'], $fqcn),
                        new FindingLocation($path, $nameNode->getLine(), $fqcn)
                    );
                } else {
                    $findings[] = new Finding(
                        'FABRYQ.GLOBAL_COMPONENT.REFERENCES_APP',
                        'BLOCKER',
                        sprintf('Global component references %s.', $fqcn),
                        new FindingLocation($path, $nameNode->getLine(), $fqcn)
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
}