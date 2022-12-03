<?php

/**
 * Entrypoint for entire application
 */

use App\Application;
use App\Config;
use App\Logger;

// Make our life easier when dealing with paths. Everything is relative to the application root via getcwd() now.
chdir(dirname(__DIR__));

// Set handler for fatal errors before loading PHP files - should not depend on any dependencies as much as possible
register_shutdown_function(function () {
    $error = error_get_last();
    if (! $error || $error['type'] !== E_ERROR) { // skip if no error or not fatal error
        return;
    }

    // Output error details to console
    handleFatalError(json_encode($error));
});

// Load PHP files and run
try {
    // Composer autoloading
    require 'vendor/autoload.php';

    // Init config and logger
    Config::init(getcwd() . DIRECTORY_SEPARATOR . 'config');
    Logger::init();

    // Run the application
    $app = new Application();
    $app->run();
} catch (Throwable $t) {
    handleFatalError($t->__toString());
}

/**
 * Handle fatal error - cannot use Logger or Response classes as fatal error may have been due to them
 *
 * @param string $message
 */
function handleFatalError($message)
{
    $utcDate = new DateTime('now', new DateTimeZone('UTC'));

    $fileHandle = fopen('php://stdout', 'w');
    fwrite(
        $fileHandle,
        '[' . $utcDate->format('Y-m-d\TH:i:s.up') . '] [FATAL] '
        . str_replace(["\n", "\r", "\t"], ' ', $message) . PHP_EOL
    );
    fclose($fileHandle);

    // Do not show error details to client
    http_response_code(500);
    header('Content-Type: text/html');
    echo 'Fatal error occurred.';
    exit;
}
