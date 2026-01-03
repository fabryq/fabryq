<?php

/**
 * Tests for the import pruner used by fix:crossing.
 *
 * @package   Fabryq\Tests
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Tests;

use Fabryq\Cli\Fix\ImportPruner;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

final class ImportPrunerTest extends TestCase
{
    public function testPruneUnresolvableImportsRequiresAutoload(): void
    {
        $stmts = $this->parseSample();
        $pruner = new ImportPruner(false);
        $pruned = $pruner->prune($stmts, true);

        $imports = $this->collectImports($pruned);
        $this->assertContains('DateTimeImmutable', $imports);
        $this->assertContains('Vendor\\Missing\\Ghost', $imports);
    }

    public function testPruneUnresolvableImportsWhenAutoloadAvailable(): void
    {
        $stmts = $this->parseSample();
        $pruner = new ImportPruner(true);
        $pruned = $pruner->prune($stmts, true);

        $imports = $this->collectImports($pruned);
        $this->assertContains('DateTimeImmutable', $imports);
        $this->assertNotContains('Vendor\\Missing\\Ghost', $imports);
    }

    /**
     * @return array<int, Node\Stmt>
     */
    private function parseSample(): array
    {
        $code = <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Billing\Payments;

use DateTimeImmutable;
use Vendor\Missing\Ghost;

final class Sample
{
    private DateTimeImmutable $date;
    private Ghost $ghost;
}
PHP;

        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $stmts = $parser->parse($code);
        $this->assertNotNull($stmts);

        return $stmts ?? [];
    }

    /**
     * @param array<int, Node\Stmt> $stmts
     *
     * @return array<int, string>
     */
    private function collectImports(array $stmts): array
    {
        $imports = [];
        $finder = new NodeFinder();
        foreach ($finder->findInstanceOf($stmts, Node\Stmt\UseUse::class) as $useUse) {
            $imports[] = $useUse->name->toString();
        }

        return $imports;
    }
}
