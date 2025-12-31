<?php

/**
 * Fabryq context tests.
 *
 * @package   Fabryq\Tests
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Tests;

use DateTimeImmutable;
use Fabryq\Tests\Support\TestContextFactory;
use PHPUnit\Framework\TestCase;

final class RuntimeContextTest extends TestCase
{
    public function testContextExposesUtilities(): void
    {
        $now = new DateTimeImmutable('2024-02-01T10:11:12+00:00');
        $payload = TestContextFactory::create($now, '01ARZ3NDEKTSV4RRFFQ69G5FAV');

        $context = $payload['context'];
        $logger = $payload['logger'];

        $this->assertSame($now, $context->clock->now());
        $this->assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', $context->ulids->create()->toBase32());

        $context->logger->info('hello');
        $this->assertSame(1, $logger->count());
    }

    public function testContextIsReadOnly(): void
    {
        $payload = TestContextFactory::create();
        $context = $payload['context'];

        $this->expectException(\Error::class);
        $context->logger = $payload['logger'];
    }
}
