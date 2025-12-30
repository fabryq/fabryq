<?php

/**
 * Doctrine validation gate for Fabryq projects.
 *
 * @package Fabryq\Cli\Gate
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Gate;

use Fabryq\Cli\Report\Finding;
use Fabryq\Cli\Report\FindingLocation;
use Fabryq\Runtime\Registry\AppRegistry;
use Fabryq\Runtime\Util\ComponentSlugger;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use Symfony\Component\Finder\Finder;

/**
 * Enforces Doctrine entity and migration conventions.
 */
final class DoctrineGate
{
    /**
     * @param AppRegistry $appRegistry Registry of discovered apps.
     * @param ComponentSlugger $slugger Slug generator for component names.
     */
    public function __construct(
        /**
         * Registry of applications used for validation.
         *
         * @var AppRegistry
         */
        private readonly AppRegistry $appRegistry,
        /**
         * Slug generator for naming conventions.
         *
         * @var ComponentSlugger
         */
        private readonly ComponentSlugger $slugger,
    ) {
    }

    /**
     * Run Doctrine validation checks for the project.
     *
     * Side effects:
     * - Reads filesystem contents and parses PHP source files.
     *
     * @param string $projectDir Absolute project directory.
     *
     * @return Finding[]
     */
    public function check(string $projectDir): array
    {
        $findings = [];
        $appsDir = $projectDir.'/src/Apps';
        $componentsDir = $projectDir.'/src/Components';

        $appIdsByFolder = [];
        foreach ($this->appRegistry->getApps() as $app) {
            $appIdsByFolder[basename($app->path)] = $app->manifest->appId;
        }

        if (is_dir($componentsDir)) {
            $finder = new Finder();
            $finder->directories()->in($componentsDir)->name('Entity');
            foreach ($finder as $entityDir) {
                $findings[] = new Finding(
                    'FABRYQ.GLOBAL_COMPONENT.PERSISTENCE_FORBIDDEN',
                    'BLOCKER',
                    'Global components must not define entities.',
                    new FindingLocation($entityDir->getPathname(), null, null)
                );
            }

            $finder = new Finder();
            $finder->directories()->in($componentsDir)->path('#Resources/migrations$#');
            foreach ($finder as $migrationDir) {
                $findings[] = new Finding(
                    'FABRYQ.GLOBAL_COMPONENT.PERSISTENCE_FORBIDDEN',
                    'BLOCKER',
                    'Global components must not define migrations.',
                    new FindingLocation($migrationDir->getPathname(), null, null)
                );
            }
        }

        if (!is_dir($appsDir)) {
            return $findings;
        }

        $finder = new Finder();
        $finder->directories()->in($appsDir)->name('Entity');
        foreach ($finder as $entityDir) {
            $relative = ltrim(str_replace($appsDir.'/', '', $entityDir->getPathname()), '/');
            $parts = explode('/', $relative);
            if (count($parts) !== 3 || $parts[2] !== 'Entity') {
                $findings[] = new Finding(
                    'FABRYQ.ENTITY.OUTSIDE_APP_COMPONENT',
                    'BLOCKER',
                    'Entities must live under src/Apps/<App>/<Component>/Entity.',
                    new FindingLocation($entityDir->getPathname(), null, null)
                );
            }
        }

        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $nodeFinder = new NodeFinder();

        $finder = new Finder();
        $finder->files()->in($appsDir)->path('#/Entity/#')->name('*.php');
        foreach ($finder as $file) {
            $path = $file->getPathname();
            $relative = ltrim(str_replace($appsDir.'/', '', $path), '/');
            $parts = explode('/', $relative);
            if (count($parts) < 4) {
                $findings[] = new Finding(
                    'FABRYQ.ENTITY.OUTSIDE_APP_COMPONENT',
                    'BLOCKER',
                    'Entities must live under src/Apps/<App>/<Component>/Entity.',
                    new FindingLocation($path, null, null)
                );
                continue;
            }

            $appFolder = $parts[0];
            $componentFolder = $parts[1];
            $appId = $appIdsByFolder[$appFolder] ?? null;
            if ($appId === null) {
                continue;
            }

            $componentSlug = $this->slugger->slug($componentFolder);
            $requiredPrefix = sprintf('app_%s__%s__', $appId, $componentSlug);

            try {
                $ast = $parser->parse($file->getContents());
            } catch (\Throwable $exception) {
                continue;
            }

            if ($ast === null) {
                continue;
            }

            $traverser = new NodeTraverser();
            $traverser->addVisitor(new NameResolver());
            $ast = $traverser->traverse($ast);

            $classNodes = $nodeFinder->findInstanceOf($ast, Node\Stmt\Class_::class);
            foreach ($classNodes as $classNode) {
                $tableName = $this->findTableName($classNode);
                if ($tableName === null) {
                    $findings[] = new Finding(
                        'FABRYQ.ENTITY.MISSING_TABLE_NAME',
                        'BLOCKER',
                        'Entity is missing an explicit table name.',
                        new FindingLocation($path, $classNode->getLine(), null)
                    );
                } elseif (!str_starts_with($tableName, $requiredPrefix)) {
                    $findings[] = new Finding(
                        'FABRYQ.ENTITY.TABLE_PREFIX_INVALID',
                        'BLOCKER',
                        sprintf('Table name "%s" must start with "%s".', $tableName, $requiredPrefix),
                        new FindingLocation($path, $classNode->getLine(), $tableName)
                    );
                }

                foreach ($this->findJoinTableNames($classNode) as $joinTable) {
                    if (!str_starts_with($joinTable, $requiredPrefix)) {
                        $findings[] = new Finding(
                            'FABRYQ.ENTITY.JOIN_TABLE_PREFIX_INVALID',
                            'BLOCKER',
                            sprintf('Join table name "%s" must start with "%s".', $joinTable, $requiredPrefix),
                            new FindingLocation($path, $classNode->getLine(), $joinTable)
                        );
                    }
                }
            }
        }

        return $findings;
    }

    /**
     * Find an explicit table name on an entity class.
     *
     * @param Node\Stmt\Class_ $classNode Parsed class node.
     *
     * @return string|null Table name or null when missing.
     */
    private function findTableName(Node\Stmt\Class_ $classNode): ?string
    {
        foreach ($classNode->attrGroups as $group) {
            foreach ($group->attrs as $attr) {
                $attributeName = $this->resolveAttributeName($attr);
                if (!in_array($attributeName, ['Doctrine\\ORM\\Mapping\\Table', 'ORM\\Table', 'Table'], true)) {
                    continue;
                }

                $name = $this->extractNameArgument($attr);
                if (is_string($name)) {
                    return $name;
                }
            }
        }

        return null;
    }

    /**
     * Find join table names on entity properties.
     *
     * @param Node\Stmt\Class_ $classNode Parsed class node.
     *
     * @return string[]
     */
    private function findJoinTableNames(Node\Stmt\Class_ $classNode): array
    {
        $names = [];
        foreach ($classNode->getProperties() as $property) {
            foreach ($property->attrGroups as $group) {
                foreach ($group->attrs as $attr) {
                    $attributeName = $this->resolveAttributeName($attr);
                    if (!in_array($attributeName, ['Doctrine\\ORM\\Mapping\\JoinTable', 'ORM\\JoinTable', 'JoinTable'], true)) {
                        continue;
                    }
                    $name = $this->extractNameArgument($attr);
                    if (is_string($name)) {
                        $names[] = $name;
                    }
                }
            }
        }

        return $names;
    }

    /**
     * Resolve the fully qualified name for an attribute node.
     *
     * @param Node\Attribute $attr Attribute node to resolve.
     *
     * @return string Resolved attribute name.
     */
    private function resolveAttributeName(Node\Attribute $attr): string
    {
        $resolved = $attr->name->getAttribute('resolvedName');
        if ($resolved instanceof Node\Name) {
            return $resolved->toString();
        }

        return $attr->name->toString();
    }

    /**
     * Extract the "name" argument from an attribute node when available.
     *
     * @param Node\Attribute $attr Attribute node to inspect.
     *
     * @return string|null Name argument value or null when not found.
     */
    private function extractNameArgument(Node\Attribute $attr): ?string
    {
        foreach ($attr->args as $index => $arg) {
            if ($arg->name !== null && $arg->name->toString() !== 'name') {
                continue;
            }
            if ($arg->name !== null || $index === 0) {
                if ($arg->value instanceof Node\Scalar\String_) {
                    return $arg->value->value;
                }
            }
        }

        return null;
    }
}
