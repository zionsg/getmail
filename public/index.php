<?php

/**
 * Entrypoint for entire application
 */

use App\Application;

// Make our life easier when dealing with paths. Everything is relative to the application root via getcwd() now.
chdir(dirname(__DIR__));

// Set handler for fatal errors before loading PHP files - should not depend on any dependencies as much as possible
register_shutdown_function(function () {
    $error = error_get_last();
    $fatalErrorTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR];
    if (! $error || ! in_array($error['type'], $fatalErrorTypes)) { // skip if no error or not fatal error
        return;
    }

    // Output error details to console
    handleFatalError(json_encode($error));
});

// Load PHP files and run
try {
    // Composer autoloading
    require 'vendor/autoload.php';

    // Run the application
    $app = new Application(getcwd() . DIRECTORY_SEPARATOR . 'config');
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
    $utcDate = new DateTimeImmutable('now', new DateTimeZone('UTC'));

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
