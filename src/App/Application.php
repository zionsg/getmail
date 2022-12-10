<?php

namespace App;

use App\Config;
use App\Constants;
use App\Logger;
use App\Router;
use App\Controller\ErrorController;
use Laminas\Diactoros\ServerRequestFactory;

/**
 * Main application class
 */
class Application
{
    /**
     * Application config
     *
     * @var Config
     */
    protected $config = null;

    /**
     * Logger
     *
     * @var Logger
     */
    protected $logger = null;

    /**
     * Router
     *
     * @var Router
     */
    protected $router = null;

    /**
     * Constructor
     *
     * @param string $configPath Absolute path to directory containing
     *     configuration files.
     */
    public function __construct(string $configPath)
    {
        $this->config = new Config($configPath);
        $this->logger = new Logger($this->config);
        $this->router = new Router($this->config, $this->logger);
    }

    /**
     * Run application
     *
     * @return void
     */
    public function run(): void
    {
        $request = ServerRequestFactory::fromGlobals();
        $request = $request->withHeader(
            Constants::HEADER_REQUEST_ID,
            Utils::generateId(($request->getServerParams())['REQUEST_TIME_FLOAT'] ?? 0)
        );

        $fallbackHandler = new ErrorController($this->config, $this->logger);
        $this->router->process($request, $fallbackHandler);
    }
}
