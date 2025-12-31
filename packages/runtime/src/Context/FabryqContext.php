<?php

/**
 * Minimal runtime context for shared utilities.
 *
 * @package   Fabryq\Runtime\Context
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Runtime\Context;

use App\Kernel;
use Fabryq\Runtime\Clock\ClockInterface;
use Fabryq\Runtime\Ulid\UlidFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Provides common utilities without acting as a container.
 */
final readonly class FabryqContext
{
    /**
     * @param LoggerInterface      $logger Logger instance.
     * @param ClockInterface       $clock  Clock instance.
     * @param UlidFactoryInterface $ulids  ULID factory.
     */
    public function __construct(
        public LoggerInterface      $logger,
        public ClockInterface       $clock,
        public UlidFactoryInterface $ulids,
        public Kernel               $kernel,
    ) {}
}
