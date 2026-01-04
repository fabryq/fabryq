<?php

/**
 * Doctrine validation gate for Fabryq projects.
 *
 * @package   Fabryq\Cli\Gate
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Gate;

use Fabryq\Cli\Report\Finding;
use Fabryq\Cli\Report\FindingLocation;
use Fabryq\Cli\Report\Severity;
use Fabryq\Runtime\Registry\AppRegistry;
use Fabryq\Runtime\Util\ComponentSlugger;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use Symfony\Component\Finder\Finder;

/**
 * Enforces Doctrine entity and migration conventions.
 */
final readonly class DoctrineGate
{
    /**
     * @param AppRegistry      $appRegistry Registry of discovered apps.
     * @param ComponentSlugger $slugger     Slug generator for component names.
     */
    public function __construct(
        /**
         * Registry of applications used for validation.
         *
         * @var AppRegistry
         */
        private AppRegistry      $appRegistry,
        /**
         * Slug generator for naming conventions.
         *
         * @var ComponentSlugger
         */
        private ComponentSlugger $slugger,
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
        $appsDir = $projectDir . '/src/Apps';

        if (!is_dir($appsDir)) {
            return [];
        }

        $finder = (new Finder())
            ->files()
            ->in($appsDir)
            ->path('Entity')
            ->name('*.php');

        foreach ($finder as $file) {
            $path = $file->getPathname();
            $appId = $this->resolveAppId($projectDir, $path);

            if ($appId === null) {
                continue;
            }

            $table = $this->extractTableName($path);
            if ($table === null) {
                continue;
            }

            // Check if table name is prefixed with app_id_
            $expectedPrefix = 'app_' . $appId . '_';
            if (!str_starts_with($table, $expectedPrefix)) {
                $findings[] = new Finding(
                    'FABRYQ.DOCTRINE.TABLE_PREFIX',
                    Severity::BLOCKER,
                    sprintf('Entity table "%s" must be prefixed with "%s".', $table, $expectedPrefix),
                    new FindingLocation($path, 1, $table)
                );
            }
        }

        return $findings;
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

    /**
     * Extract the table name from an entity class attribute.
     *
     * Side effects:
     * - Reads the file contents from disk.
     * - Parses PHP source into an AST.
     *
     * @param string $path Absolute path to the entity PHP file.
     *
     * @return string|null Table name or null when unavailable.
     */
    private function extractTableName(string $path): ?string
    {
        $code = file_get_contents($path);

        // Use createForNewestSupportedVersion() to support modern syntax.
        $parser = (new ParserFactory())->createForNewestSupportedVersion();

        try {
            $ast = $parser->parse($code);
        } catch (\Throwable $e) {
            return null;
        }

        if ($ast === null) {
            return null;
        }

        $nodeFinder = new NodeFinder();
        /** @var Node\Stmt\Class_|null $classNode */
        $classNode = $nodeFinder->findFirstInstanceOf($ast, Node\Stmt\Class_::class);

        if ($classNode === null) {
            return null;
        }

        // Check Attributes (PHP 8+)
        foreach ($classNode->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attribute) {
                $name = $attribute->name->toString();
                if ($name === 'Doctrine\ORM\Mapping\Table' || $name === 'ORM\Table') {
                    foreach ($attribute->args as $arg) {
                        if ($arg->name?->toString() === 'name') {
                            return $this->getStringValue($arg->value);
                        }
                    }
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
     * Extract a string scalar value from an expression node.
     *
     * @param Node\Expr $expr Expression node to inspect.
     *
     * @return string|null String literal value or null when not a string.
     */
    private function getStringValue(Node\Expr $expr): ?string
    {
        if ($expr instanceof Node\Scalar\String_) {
            return $expr->value;
        }
        return null;
    }

    /**
     * Resolve the application ID based on a source path.
     *
     * @param string $projectDir Absolute project directory.
     * @param string $path       Absolute file path within the project.
     *
     * @return string|null App ID or null when the path does not match the expected layout.
     */
    private function resolveAppId(string $projectDir, string $path): ?string
    {
        $relative = ltrim(str_replace($projectDir, '', $path), '/');
        $parts = explode('/', $relative);

        return isset($parts[2]) ? strtolower($parts[2]) : null;
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
}
