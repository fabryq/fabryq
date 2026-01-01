<?php

/**
 * Base command with Fabryq context.
 *
 * @package   Fabryq\Runtime\Command
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Runtime\Command;

use Fabryq\Runtime\Context\FabryqContext;
use Symfony\Component\Console\Command\Command;

/**
 * Provides standard context utilities for console commands.
 */
abstract class AbstractFabryqCommand extends Command
{
    /**
     * @param FabryqContext $ctx Runtime context.
     */
    public function __construct(
        protected readonly FabryqContext $ctx,
    ) {
        parent::__construct();
    }
}
