<?php

/**
 * Service fixture for context tests.
 *
 * @package   Fabryq\Tests\Fixtures\Runtime
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Tests\Fixtures\Runtime;

use DateTimeImmutable;
use Fabryq\Runtime\Context\FabryqContext;

/**
 * Simple service that uses the Fabryq context.
 */
final class TestService
{
    public function __construct(
        private FabryqContext $ctx
    ) {
    }

    public function generateId(): string
    {
        return $this->ctx->ulids->create()->toBase32();
    }

    public function now(): DateTimeImmutable
    {
        return $this->ctx->clock->now();
    }

    public function log(string $message): void
    {
        $this->ctx->logger->info($message);
    }
}
