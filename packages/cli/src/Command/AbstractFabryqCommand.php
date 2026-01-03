<?php

/**
 * Base command for Fabryq CLI commands.
 *
 * @package   Fabryq\Cli\Command
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;

/**
 * Adds shared CLI options for Fabryq commands.
 */
abstract class AbstractFabryqCommand extends Command
{
    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this->addOption('debug', null, InputOption::VALUE_NONE, 'Show a stack trace on errors.');
    }
}
