<?php

/**
 * Application bootstrap for environment configuration.
 *
 * @package   App\Config
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

if (!isset($_SERVER['APP_ENV'])) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}
