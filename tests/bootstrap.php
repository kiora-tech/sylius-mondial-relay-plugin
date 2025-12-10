<?php

declare(strict_types=1);

/*
 * This file is part of the Kiora Sylius Mondial Relay Plugin.
 *
 * (c) Kiora <https://kiora.tech>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// Ensure we can load Composer autoloader
$autoloadFiles = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../../../vendor/autoload.php',
];

$autoloaderFound = false;
foreach ($autoloadFiles as $autoloadFile) {
    if (file_exists($autoloadFile)) {
        require_once $autoloadFile;
        $autoloaderFound = true;
        break;
    }
}

if (!$autoloaderFound) {
    throw new \RuntimeException(
        'Could not find Composer autoloader. Run "composer install" first.'
    );
}

// Set error reporting for tests
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Set timezone to avoid warnings
date_default_timezone_set('UTC');
