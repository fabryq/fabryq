<?php

/**
 * Base controller with Fabryq context.
 *
 * @package Fabryq\Runtime\Controller
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Runtime\Controller;

use Fabryq\Runtime\Context\FabryqContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Provides standard context utilities for controllers.
 */
abstract class AbstractFabryqController extends AbstractController
{
    /**
     * @param FabryqContext $ctx Fabryq runtime context.
     */
    public function __construct(
        protected readonly FabryqContext $ctx,
    ) {
    }
}
