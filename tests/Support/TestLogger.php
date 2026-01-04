<?php

/**
 * Simple in-memory logger for tests.
 *
 * @package   Fabryq\Tests\Support
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Tests\Support;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Captures log entries for assertions.
 */
final class TestLogger implements LoggerInterface
{
    /**
     * @var array<int, array{level:string, message:string, context:array<string, mixed>}>
     */
    private array $entries = [];

    /**
     * @return array<int, array{level:string, message:string, context:array<string, mixed>}>
     */
    public function entries(): array
    {
        return $this->entries;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->entries);
    }

    /**
     * {@inheritDoc}
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        if (is_string($level)) {
            $levelValue = $level;
        } elseif (is_int($level) || is_float($level) || is_bool($level)) {
            $levelValue = (string) $level;
        } elseif ($level instanceof \Stringable) {
            $levelValue = (string) $level;
        } else {
            $levelValue = gettype($level);
        }

        $this->entries[] = [
            'level' => $levelValue,
            'message' => (string)$message,
            'context' => $context,
        ];
    }

    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }
}
