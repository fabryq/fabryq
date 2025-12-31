<?php

/**
 * Base use-case with Fabryq context.
 *
 * @package Fabryq\Runtime\UseCase
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Runtime\UseCase;

use Fabryq\Runtime\Context\FabryqContext;

/**
 * Provides standard context utilities for use cases.
 */
abstract class AbstractFabryqUseCase
{
    /**
     * @param FabryqContext $ctx Fabryq runtime context.
     */
    public function __construct(
        protected readonly FabryqContext $ctx,
    ) {
    }
}
