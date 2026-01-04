<?php

/**
 * Write lock for Fabryq CLI commands.
 *
 * @package   Fabryq\Cli\Lock
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace Fabryq\Cli\Lock;

use Fabryq\Cli\Error\ProjectStateError;

/**
 * Acquires a single-writer lock for write operations.
 */
final class WriteLock
{
    /**
     * @var resource|null
     */
    private $handle;

    /**
     * @param string $projectDir Absolute project directory.
     */
    public function __construct(
        private readonly string $projectDir,
    ) {
    }

    /**
     * Acquire the write lock.
     */
    public function acquire(): void
    {
        $lockPath = rtrim($this->projectDir, '/') . '/var/lock/fabryq.lock';
        $lockDir = dirname($lockPath);
        if (!is_dir($lockDir) && !@mkdir($lockDir, 0775, true) && !is_dir($lockDir)) {
            throw new ProjectStateError('Unable to create lock directory: ' . $lockDir);
        }

        $handle = @fopen($lockPath, 'c+');
        if (!is_resource($handle)) {
            throw new ProjectStateError('Unable to open lock file: ' . $lockPath);
        }

        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);
            throw new ProjectStateError('Fabryq lock is already held (var/lock/fabryq.lock).');
        }

        $this->handle = $handle;
    }

    /**
     * Release the lock if held.
     */
    public function release(): void
    {
        if (!is_resource($this->handle)) {
            return;
        }

        flock($this->handle, LOCK_UN);
        fclose($this->handle);
        $this->handle = null;
    }

    public function __destruct()
    {
        $this->release();
    }
}
