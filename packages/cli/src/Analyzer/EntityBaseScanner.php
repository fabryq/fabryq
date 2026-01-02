<?php

/**
 * Analyzer that enforces entity base usage.
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
use PhpParser\ParserFactory;
use Symfony\Component\Finder\Finder;

/**
 * Scans for Doctrine entities that do not use the Fabryq entity base.
 */
final class EntityBaseScanner
{
    private const ENTITY_ATTRIBUTE = 'Doctrine\\ORM\\Mapping\\Entity';

    private const BASE_CLASS = 'Fabryq\\Runtime\\Entity\\AbstractFabryqEntity';

    private const BASE_INTERFACE = 'Fabryq\\Runtime\\Entity\\FabryqEntityInterface';

    private const BASE_TRAIT = 'Fabryq\\Runtime\\Entity\\FabryqEntityTrait';

    /**
     * Scan the project for entity base violations.
     *
     * @param string $projectDir Absolute project directory.
     *
     * @return Finding[]
     */
    public function scan(string $projectDir): array
    {
        $paths = [];
        $appsDir = $projectDir . '/src/Apps';
        $componentsDir = $projectDir . '/src/Components';

        if (is_dir($appsDir)) {
            $paths[] = $appsDir;
        }

        if (is_dir($componentsDir)) {
            $paths[] = $componentsDir;
        }

        if ($paths === []) {
            return [];
        }

        $finder = (new Finder())
            ->files()
            ->in($paths)
            ->path('Entity')
            ->name('*.php');

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
            $traverser->addVisitor(new NameResolver());
            $ast = $traverser->traverse($ast);

            /** @var Node\Stmt\Class_[] $classes */
            $classes = $nodeFinder->findInstanceOf($ast, Node\Stmt\Class_::class);
            foreach ($classes as $classNode) {
                if (!$this->isDoctrineEntity($classNode)) {
                    continue;
                }

                $className = $this->resolveClassName($classNode, $path);
                $line = $classNode->getLine();

                if ($this->extendsBase($classNode)) {
                    continue;
                }

                $implementsInterface = $this->implementsInterface($classNode);
                $usesTrait = $this->usesTrait($classNode);

                if ($implementsInterface && $usesTrait) {
                    $findings[] = new Finding(
                        'FABRYQ.ENTITY.BASE_REQUIRED',
                        'WARNING',
                        sprintf('Doctrine entity "%s" uses trait-based base; prefer AbstractFabryqEntity.', $className),
                        new FindingLocation($path, $line, $className),
                        ['primary' => $className . '|trait-exception'],
                        'Prefer extending AbstractFabryqEntity when possible.'
                    );
                    continue;
                }

                $findings[] = new Finding(
                    'FABRYQ.ENTITY.BASE_REQUIRED',
                    'BLOCKER',
                    sprintf('Doctrine entity "%s" must extend AbstractFabryqEntity.', $className),
                    new FindingLocation($path, $line, $className),
                    ['primary' => $className . '|missing-base'],
                    'Extend AbstractFabryqEntity or implement FabryqEntityInterface and use FabryqEntityTrait.'
                );
            }
        }

        return $findings;
    }

    /**
     * Check whether the class extends the Fabryq base entity.
     *
     * @param Node\Stmt\Class_ $classNode Class node.
     *
     * @return bool
     */
    private function extendsBase(Node\Stmt\Class_ $classNode): bool
    {
        if ($classNode->extends === null) {
            return false;
        }

        $resolved = $classNode->extends->getAttribute('resolvedName');
        $fqcn = $resolved instanceof Node\Name ? $resolved->toString() : $classNode->extends->toString();

        return $fqcn === self::BASE_CLASS;
    }

    /**
     * Check whether the class implements the Fabryq entity interface.
     *
     * @param Node\Stmt\Class_ $classNode Class node.
     *
     * @return bool
     */
    private function implementsInterface(Node\Stmt\Class_ $classNode): bool
    {
        foreach ($classNode->implements as $interface) {
            $resolved = $interface->getAttribute('resolvedName');
            $fqcn = $resolved instanceof Node\Name ? $resolved->toString() : $interface->toString();
            if ($fqcn === self::BASE_INTERFACE) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether a class is marked as a Doctrine entity.
     *
     * @param Node\Stmt\Class_ $classNode Class node.
     *
     * @return bool
     */
    private function isDoctrineEntity(Node\Stmt\Class_ $classNode): bool
    {
        foreach ($classNode->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attribute) {
                $name = $attribute->name;
                if (!$name instanceof Node\Name) {
                    continue;
                }
                $resolved = $name->getAttribute('resolvedName');
                $fqcn = $resolved instanceof Node\Name ? $resolved->toString() : $name->toString();
                if ($fqcn === self::ENTITY_ATTRIBUTE || $fqcn === 'ORM\\Entity' || $fqcn === 'Entity') {
                    return true;
                }
            }
        }

        $doc = $classNode->getDocComment();
        if ($doc !== null) {
            $text = $doc->getText();
            if (str_contains($text, '@ORM\\Entity') || str_contains($text, '@Entity') || str_contains($text, '@Doctrine\\ORM\\Mapping\\Entity')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve the class name for reporting.
     *
     * @param Node\Stmt\Class_ $classNode Class node.
     * @param string           $path      File path for fallback.
     *
     * @return string
     */
    private function resolveClassName(Node\Stmt\Class_ $classNode, string $path): string
    {
        if (property_exists($classNode, 'namespacedName') && $classNode->namespacedName instanceof Node\Name) {
            return $classNode->namespacedName->toString();
        }

        if ($classNode->name instanceof Node\Identifier) {
            return $classNode->name->toString();
        }

        return $path;
    }

    /**
     * Check whether the class uses the Fabryq entity trait.
     *
     * @param Node\Stmt\Class_ $classNode Class node.
     *
     * @return bool
     */
    private function usesTrait(Node\Stmt\Class_ $classNode): bool
    {
        foreach ($classNode->getTraitUses() as $traitUse) {
            foreach ($traitUse->traits as $trait) {
                $resolved = $trait->getAttribute('resolvedName');
                $fqcn = $resolved instanceof Node\Name ? $resolved->toString() : $trait->toString();
                if ($fqcn === self::BASE_TRAIT) {
                    return true;
                }
            }
        }

        return false;
    }
}
