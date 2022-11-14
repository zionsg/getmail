<?php
/**
 * Entrypoint for application
 */

use App\ApiResponse;
use App\Application;
use App\Config;
use App\Logger;

// Make our life easier when dealing with paths. Everything is relative to the application root now.
chdir(dirname(__DIR__));

// Set additional environment variables before loading PHP files
putenv('GETMAIL_VERSION=' . trim(file_get_contents('VERSION.txt') ?: 'no-version'));

// Composer autoloading
require 'vendor/autoload.php';

try {
    // Get config and init logger
    $appConfig = new Config();
    Logger::init($appConfig);

    // Run the application
    $app = new Application($appConfig);
    $app->run();
} catch (Throwable $t) {
    Logger::errorLog($t);

    $response = new ApiResponse(500, $t->getMessage());
    $response->send();
}
