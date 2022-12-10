<?php

namespace App;

use App\Config;
use App\Constants;
use App\Utils;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

/**
 * Logger class
 *
 * Logs are written to php://stdout so that they can appear in Docker container
 * logs.
 *
 * @link See https://www.php-fig.org/psr/psr-3/ on signature of logging methods.
 */
class Logger extends AbstractLogger
{
    /**
     * Application config
     *
     * @var Config
     */
    protected $config = null;

    /**
     * Application name
     *
     * @var string
     */
    protected $appName = '';

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
     * Log level priority to cap at
     *
     * @var int
     */
    protected $logLevelPriority = 0;

    /**
     * File handle for writing to stdout
     *
     * @var resource
     */
    protected $fileHandle = null;

    /**
     * Priorities for log levels, where smaller numbers have higher priority.
     *
     * @link See https://www.rfc-editor.org/rfc/rfc5424 on the numerical codes for severity levels.
     * @var array
     */
    protected $logLevelPriorities = [
        LogLevel::EMERGENCY => 0,
        LogLevel::ALERT => 1,
        LogLevel::CRITICAL => 2,
        LogLevel::ERROR => 3,
        LogLevel::WARNING => 4,
        LogLevel::NOTICE => 5,
        LogLevel::INFO => 6,
        LogLevel::DEBUG => 7,
    ];

    /**
     * Constructor
     *
     * @param Config $config Application config.
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->appName = $this->config->getApplicationName();
        $this->env = $this->config->getDeploymentEnvironment();
        $this->version = $this->config->getVersion();
        $this->logLevelPriority = $this->logLevelPriorities[$this->config->get('log_level')] ?? 0;
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
     * Logs with an arbitrary level.
     *
     * @see AbstractLogger::log()
     * @param mixed  $level
     * @param string|\Stringable $message
     * @param array  $context
     * @return void
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        // Do not log if priority of this message's log level is lower than the priority of the configured log level
        $priority = $this->logLevelPriorities[$level] ?? 0;
        if ($priority > $this->logLevelPriority) {
            return;
        }

        // The caller typically calls a static method or instance method in this
        // class, e.g. Logger::infoLog() or (new Logger())->info(), which then
        // calls this method, hence checking 3rd stack frame in the backtrace.
        $backtrace = debug_backtrace(2, 3); // exclude populating of object & args for backtrace hence 2 for 1st arg
        $caller = $backtrace[2] ?? []; // 3rd stack frame is array element 2

        // Current request if any
        $request = $context['request'] ?? null;
        $requestId = ($request instanceof ServerRequestInterface)
            ? $request->getHeaderLine(Constants::HEADER_REQUEST_ID)
            : '';

        // Server params
        $serverParams = ($request instanceof ServerRequestInterface) ? $request->getServerParams() : $_SERVER;

        // Newlines should be removed else log aggregators such as AWS CloudWatch may interpret as multiple logs.
        // Application name is used to differentiate logs from different apps, especially when aggregated together.
        // Sample log entry (split into many lines here for easier reading but will be output as 1 line when logged):
        //    [2022-11-24T01:57:32.095364Z] [INFO] [APP NAME] [/var/www/html/src/App/Application.php:19]
        //        [MSG Application started.]
        //        [REQ 172.18.0.1:54112 GET text/html /web "Mozilla/5.0 (Windows NT 10.0; Win64; x64)"
        //             1669950476.198900Z-4b340550242239.64159797]
        //        [SVR 172.18.0.2:80 production v0.1.0-master-5ba4945-20221123T0600Z]
        $text = str_replace(["\n", "\r", "\t"], ' ', sprintf(
            '[%s] [%s] [%s] [%s:%s] [MSG %s] [REQ %s:%s %s %s %s "%s" %s] [SVR %s:%s %s %s]',
            Utils::utcNow(true),
            strtoupper($level),
            $this->appName,
            $caller['file'] ?? 'no-file',
            $caller['line'] ?? 0,
            $message,
            $serverParams['REMOTE_ADDR'],
            $serverParams['REMOTE_PORT'],
            $serverParams['REQUEST_METHOD'],
            ($serverParams['CONTENT_TYPE'] ?: 'no-content-type'),
            $serverParams['REQUEST_URI'],
            ($serverParams['HTTP_USER_AGENT'] ?: 'no-user-agent'),
            $requestId ?: 'no-request-id',
            $serverParams['SERVER_ADDR'],
            $serverParams['SERVER_PORT'],
            $this->env,
            $this->version
        ));

        // Cannot use `file_put_contents('php://stdout', $text);` cos `allow_url_fopen` may be set to false for
        // security reasons, hence the use of a file handle
        fwrite($this->fileHandle, $text . PHP_EOL); // must end with newline as next fwrite() will append to this
    }
}
