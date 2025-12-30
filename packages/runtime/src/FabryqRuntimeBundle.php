<?php

/**
 * Bundle entry point for the Fabryq runtime.
 *
 * @package Fabryq\Runtime
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Runtime;

use Fabryq\Runtime\DependencyInjection\Compiler\CapabilityProviderPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Registers compiler passes and bootstraps runtime bundle behavior.
 */
final class FabryqRuntimeBundle extends Bundle
{
    /**
     * {@inheritDoc}
     *
     * Side effects:
     * - Registers the capability provider compiler pass.
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new CapabilityProviderPass());
    }
}
