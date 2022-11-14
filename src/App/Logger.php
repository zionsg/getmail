<?php

namespace App;

use App\Config;

/**
 * Logger class
 *
 * Logging methods can also be called statically via "<log level>Log" methods,
 * e.g. Logger::infoLog() which calls (new Logger())->info(), so that no
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
    const EMERGENCY = 'emergency';
    const ALERT = 'alert';
    const CRITICAL = 'critical';
    const ERROR = 'error';
    const WARNING = 'warning';
    const NOTICE = 'notice';
    const INFO = 'info';
    const DEBUG = 'debug';

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
     * @param string $name Name of method being called.
     * @param array $arguments Arguments to be passed to method.
     * @return void
     */
    public static function __callStatic($name, $arguments)
    {
        if (substr($name, -3, 3) !== 'Log') {
            return;
        }

        $instance = self::getInstance();
        $methodName = substr($name, 0, strlen($name) - 3);
        if (method_exists($instance, $methodName)) {
            call_user_func_array([$instance, $methodName], $arguments);
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
        // Newlines should be removed else log parsers such as AWS CloudWatch may interpret as multiple logs
        $text = '[' . Config::getCurrentTimestamp(true) . ']'
            . ' [' . strtoupper($level) . ']'
            . " [{$this->logTag} {$this->env} {$_SERVER['SERVER_ADDR']}:{$_SERVER['SERVER_PORT']}]"
            . ' ' . str_replace(["\n", "\r", "\t"], ' ', $message)
            . ' [CONTEXT ' . json_encode($context) . ']'
            . ' [REQUEST ' . "{$_SERVER['SERVER_ADDR']} {$_SERVER['REMOTE_ADDR']} {$_SERVER['REQUEST_METHOD']}"
            . ' ' . ($_SERVER['CONTENT_TYPE'] ?: '-')
            . " {$_SERVER['REQUEST_URI']} \"{$_SERVER['HTTP_USER_AGENT']}\"" . ']'
            . PHP_EOL;

        // Cannot use `file_put_contents('php://stdout', $text);` cos `allow_url_fopen` may be set to false for
        // security reasons, hence the use of a file handle
        fwrite($this->fileHandle, $text);
    }

    /**
     * Get singleton instance
     *
     * @return Logger
     */
    protected static function getInstance()
    {
        return self::$instance;
    }
}
