<?php

/**
 * Finding ID determinism tests.
 *
 * @package   Fabryq\Tests
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Tests;

use Fabryq\Cli\Report\Finding;
use Fabryq\Cli\Report\FindingIdGenerator;
use Fabryq\Cli\Report\FindingLocation;
use Fabryq\Cli\Report\Severity;
use PHPUnit\Framework\TestCase;

final class FindingIdTest extends TestCase
{
    public function testDeterministicIdsIgnoreLineNumbers(): void
    {
        $projectDir = '/tmp/fabryq-test-project';
        $generator = new FindingIdGenerator($projectDir);

        $locationA = new FindingLocation($projectDir . '/src/Apps/Billing/Foo.php', 10, 'Foo');
        $locationB = new FindingLocation($projectDir . '/src/Apps/Billing/Foo.php', 42, 'Foo');

        $findingA = new Finding(
            'FABRYQ.TEST.DET',
            Severity::BLOCKER,
            'Deterministic test.',
            $locationA,
            ['primary' => 'Foo|typehint']
        );

        $findingB = new Finding(
            'FABRYQ.TEST.DET',
            Severity::BLOCKER,
            'Deterministic test.',
            $locationB,
            ['primary' => 'Foo|typehint']
        );

        $idA = $generator->generate($findingA);
        $idB = $generator->generate($findingB);

        $this->assertSame($idA, $idB);
        $this->assertMatchesRegularExpression('/^F-[0-9A-HJKMNP-TV-Z]{8}$/', $idA);
    }
}
