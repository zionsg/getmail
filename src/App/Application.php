<?php

namespace App;

use App\Config;
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

        // Add custom attributes
        $query = $request->getQueryParams();
        $request = $request
            ->withAttribute(
                // Generate unique 50-char ID for each request, e.g. 1670837243.176900-47cde7c7fead438b9a5ec2c6e961e740
                'request_id',
                str_pad(microtime(true), 17, '0', STR_PAD_RIGHT) . '-' . bin2hex(random_bytes(16)) // ensure 6-digit μs
            )
            ->withAttribute(
                // Whether to wrap HTML for view in layout template, true by default. See src/Web/view/layout.phtml.
                'layout',
                intval($query['layout'] ?? 1) // cannot use || cos value may be 0
            );

        $fallbackHandler = new ErrorController($this->config, $this->logger, $this->router);
        $this->router->process($request, $fallbackHandler);
    }
}
