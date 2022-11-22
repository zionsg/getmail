<?php

/**
 * Entrypoint for entire application
 */

// The 5 most important classes are listed here
use Api\Response as ApiResponse;
use App\Application;
use App\Config;
use App\Logger;
use Web\Response as WebResponse;

// Make our life easier when dealing with paths. Everything is relative to the application root via getcwd() now.
chdir(dirname(__DIR__));

// Set handler for fatal errors before loading PHP files - should not depend on any dependencies as much as possible
register_shutdown_function(function () {
    $error = error_get_last();
    if (! $error || $error['type'] !== E_ERROR) { // skip if no error or not fatal error
        return;
    }

    // Output error details to console
    // Logger class not used to format message/timestamp as fatal error may have been due to it
    $fileHandle = fopen('php://stdout', 'w');
    $utcDate = new DateTime('now', new DateTimeZone('UTC'));
    fwrite(
        $fileHandle,
        '[' . $utcDate->format('Y-m-d\TH:i:s.up') . '] [FATAL] ' . json_encode($error) // follow format in Logger class
    );
    fclose($fileHandle);

    // Do not show error details to client - WebResponse class not used as fatal error may have been due to it
    http_response_code(500);
    header('Content-Type: text/html');
    echo 'Fatal error occurred.';
    exit;
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
    Logger::errorLog($t);

    $response = new ApiResponse(500, 'An error occurred.'); // do not show error details to client
    $response->send();
}
