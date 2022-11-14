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

// Handle fatal errors - should not depend on any dependencies as much as possible
register_shutdown_function(function () {
    $error = error_get_last();
    if (!$error || $error['type'] !== E_ERROR) { // skip if no error or not fatal error
        return;
    }

    $response = new ApiResponse(500, $error['message']);
    $response->send();
});

try {
    // Init config and logger
    Config::init();
    Logger::init();

    // Run the application
    $app = new Application();
    $app->run();
} catch (Throwable $t) {
    Logger::errorLog($t);

    $response = new ApiResponse(500, $t->getMessage());
    $response->send();
}
