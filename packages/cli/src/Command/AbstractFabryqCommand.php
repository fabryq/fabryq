<?php

/**
 * Base command for Fabryq CLI commands.
 *
 * @package   Fabryq\Cli\Command
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Command;

use Fabryq\Cli\Error\UserError;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Adds shared CLI options for Fabryq commands.
 */
abstract class AbstractFabryqCommand extends Command
{
    /**
     * Require a non-empty string argument.
     *
     * @param InputInterface $input Console input.
     * @param string         $name  Argument name.
     *
     * @return string
     */
    protected function requireStringArgument(InputInterface $input, string $name): string
    {
        $value = $input->getArgument($name);
        if (!is_string($value) || $value === '') {
            throw new UserError(sprintf('Argument "%s" must be a non-empty string.', $name));
        }

        return $value;
    }

    /**
     * Return a string option or null when unset/empty.
     *
     * @param InputInterface $input Console input.
     * @param string         $name  Option name.
     *
     * @return string|null
     */
    protected function optionalStringOption(InputInterface $input, string $name): ?string
    {
        $value = $input->getOption($name);
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_string($value)) {
            throw new UserError(sprintf('Option "--%s" must be a string.', $name));
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this->addOption('debug', null, InputOption::VALUE_NONE, 'Show a stack trace on errors.');
    }
}
