<?php

/**
 * Console error handler for Fabryq CLI commands.
 *
 * @package   Fabryq\Cli\EventSubscriber
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\EventSubscriber;

use Fabryq\Cli\Error\CliExitCode;
use Fabryq\Cli\Error\InternalError;
use Fabryq\Cli\Error\ProjectStateError;
use Fabryq\Cli\Error\UserError;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Maps exceptions to exit codes and controlled output.
 */
final class CliErrorSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::ERROR => ['onConsoleError', 200],
        ];
    }

    /**
     * Handle console exceptions with deterministic output.
     *
     * @param ConsoleErrorEvent $event Console error event.
     */
    public function onConsoleError(ConsoleErrorEvent $event): void
    {
        $exception = $event->getError();
        $exitCode = match (true) {
            $exception instanceof UserError => CliExitCode::USER_ERROR,
            $exception instanceof ProjectStateError => CliExitCode::PROJECT_STATE_ERROR,
            $exception instanceof InternalError => CliExitCode::INTERNAL_ERROR,
            default => CliExitCode::INTERNAL_ERROR,
        };

        $event->setExitCode($exitCode);

        $io = new SymfonyStyle($event->getInput(), $event->getOutput());
        $io->error($exception->getMessage());

        if ($this->isDebug($event)) {
            $event->getOutput()->writeln('');
            $event->getOutput()->writeln((string) $exception);
        }

        $event->stopPropagation();
    }

    /**
     * Determine if debug output is requested.
     *
     * @param ConsoleErrorEvent $event Console error event.
     *
     * @return bool
     */
    private function isDebug(ConsoleErrorEvent $event): bool
    {
        $input = $event->getInput();
        if (method_exists($input, 'hasParameterOption')) {
            return $input->hasParameterOption('--debug');
        }

        return false;
    }
}
