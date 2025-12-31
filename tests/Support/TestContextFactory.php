<?php

/**
 * Factory for test contexts.
 *
 * @package   Fabryq\Tests\Support
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Tests\Support;

use DateTimeImmutable;
use Fabryq\Runtime\Context\FabryqContext;

/**
 * Builds a FabryqContext with deterministic dependencies.
 */
final class TestContextFactory
{
    /**
     * Create a context and logger for tests.
     *
     * @param DateTimeImmutable|null $now  Fixed clock timestamp.
     * @param string|null            $ulid Fixed ULID value.
     *
     * @return array{context: FabryqContext, logger: TestLogger}
     */
    public static function create(?DateTimeImmutable $now = null, ?string $ulid = null): array
    {
        $logger = new TestLogger();
        $clock = new TestClock($now ?? new DateTimeImmutable('2024-01-01T00:00:00+00:00'));
        $ulids = new TestUlidFactory($ulid ?? '01ARZ3NDEKTSV4RRFFQ69G5FAV');

        return [
            'context' => new FabryqContext($logger, $clock, $ulids),
            'logger' => $logger,
        ];
    }
}
