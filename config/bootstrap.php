<?php

/**
 * Application bootstrap for the monorepo root environment.
 *
 * @package   App\Config
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

$envFile = dirname(__DIR__) . '/.env';
if (!isset($_SERVER['APP_ENV']) && is_file($envFile)) {
    (new Dotenv())->bootEnv($envFile);
}
