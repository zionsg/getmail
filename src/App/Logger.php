<?php

namespace App;

use App\Config;

/**
 * Logger class
 *
 * Logging methods can also be called statically via "<log level>Log" methods,
 * e.g. Logger::infoLog() which calls (new Logger())->log('info'), so that no
 * instantiation is needed to use them. A singleton instance is used internally
 * for these static methods so that its destructor can be used to close the
 * file handle.
 *
 * Logs are written to php://stdout so that they can appear in Docker container
 * logs.
 *
 * @link See https://www.php-fig.org/psr/psr-3/ on signature of logging methods.
 */
class Logger
{
    /**
     * Log levels as per Psr\Log\LogLevel in PSR-3
     *
     * @var string
     */
    public const EMERGENCY = 'emergency';
    public const ALERT = 'alert';
    public const CRITICAL = 'critical';
    public const ERROR = 'error';
    public const WARNING = 'warning';
    public const NOTICE = 'notice';
    public const INFO = 'info';
    public const DEBUG = 'debug';

    /**
     * Singleton instance
     *
     * @var Logger
     */
    protected static $instance = null;

    /**
     * Deployment environment
     *
     * @var string
     */
    protected $env = '';

    /**
     * Application version
     *
     * @var string
     */
    protected $version = '';

    /**
     * Log tag
     *
     * @var string
     */
    protected $logTag = '';

    /**
     * File handle for writing to stdout
     *
     * @var resource
     */
    protected $fileHandle = null;

    /**
     * Initialize static class - this must be called right at the start
     *
     * @return void
     */
    public static function init()
    {
        self::$instance = new Logger();
    }

    /**
     * Magic method for calling logging methods statically
     *
     * Static methods cannot have the same names as instance methods, hence
     * the "Log" suffix.
     *
     * @param string $name Name of method being called.
     * @param array $arguments Arguments to be passed to method.
     * @return void
     */
    public static function __callStatic($name, $arguments)
    {
        if (substr($name, -3, 3) !== 'Log') {
            return;
        }

        $logLevel = substr($name, 0, strlen($name) - 3);
        if (defined(__CLASS__ . '::' . strtoupper($logLevel))) { // check if log level exists
            // Calling log() instead of the instance method associated with the log level,
            // e.g. info(), so that same stack frame in debug_backtrace() can be used to
            // retrieve the caller, i.e. check debug_backtrace()[2] to get caller for
            // Logger::infoLog() or (new Logger())->info().
            array_unshift($arguments, $logLevel);
            call_user_func_array([self::$instance, 'log'], $arguments);
        }
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->env = Config::getDeploymentEnvironment();
        $this->version = Config::getVersion();
        $this->logTag = Config::get('log_tag');

        $this->fileHandle = fopen('php://stdout', 'w');
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        fclose($this->fileHandle);
    }

    /**
     * System is unusable.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function emergency($message, array $context = [])
    {
        $this->log(self::EMERGENCY, $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function alert($message, array $context = [])
    {
        $this->log(self::ALERT, $message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function critical($message, array $context = [])
    {
        $this->log(self::CRITICAL, $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function error($message, array $context = [])
    {
        $this->log(self::ERROR, $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function warning($message, array $context = [])
    {
        $this->log(self::WARNING, $message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function notice($message, array $context = [])
    {
        $this->log(self::NOTICE, $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function info($message, array $context = [])
    {
        $this->log(self::INFO, $message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function debug($message, array $context = [])
    {
        $this->log(self::DEBUG, $message, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return void
     */
    public function log($level, $message, array $context = [])
    {
        // The caller typically calls a static method or instance method in this
        // class, e.g. Logger::infoLog() or (new Logger())->info(), which then
        // calls this method, hence checking 3rd stack frame in the backtrace.
        $backtrace = debug_backtrace(2, 3); // exclude populating of object & args for backtrace hence 2 for 1st arg
        $caller = $backtrace[2] ?? []; // 3rd stack frame is array element 2

        // Newlines should be removed else log aggregators such as AWS CloudWatch may interpret as multiple logs
        // Sample log entry (split into many lines here for easier reading but will be output as 1 line when logged):
        //    [2022-11-24T01:57:32.095364Z] [INFO] [LOGTAG] [/var/www/html/src/App/Application.php:19]
        //        [MSG Application started.] [CTX []]
        //        [REQ 172.18.0.1:54112 GET text/html /web "Mozilla/5.0 (Windows NT 10.0; Win64; x64)"]
        //        [SVR 172.18.0.2:80 production v0.1.0-master-5ba4945-20221123T0600Z]
        $text = str_replace(["\n", "\r", "\t"], ' ', sprintf(
            '[%s] [%s] [%s] [%s:%s] [MSG %s] [CTX %s] [REQ %s:%s %s %s %s "%s"] [SVR %s:%s %s %s]',
            Config::getCurrentTimestamp(true),
            strtoupper($level),
            $this->logTag ?: 'no-log-tag',
            $caller['file'] ?? 'no-file',
            $caller['line'] ?? 0,
            $message,
            json_encode($context),
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['REMOTE_PORT'],
            $_SERVER['REQUEST_METHOD'],
            ($_SERVER['CONTENT_TYPE'] ?: 'no-content-type'),
            $_SERVER['REQUEST_URI'],
            ($_SERVER['HTTP_USER_AGENT'] ?: 'no-user-agent'),
            $_SERVER['SERVER_ADDR'],
            $_SERVER['SERVER_PORT'],
            $this->env ?: 'no-env',
            $this->version ?: 'no-version'
        ));

        // Cannot use `file_put_contents('php://stdout', $text);` cos `allow_url_fopen` may be set to false for
        // security reasons, hence the use of a file handle
        fwrite($this->fileHandle, $text . PHP_EOL); // must end with newline as next fwrite() will append to this
    }
}
