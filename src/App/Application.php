<?php

namespace App;

use RuntimeException;
use App\Config;
use App\Logger;
use App\Router;
use App\Controller\ErrorController;
use Laminas\Diactoros\ServerRequestFactory;
use Psr\Http\Message\ResponseInterface;

/**
 * Main application class
 */
class Application
{
    /**
     * Constants to refer to custom request attributes
     *
     * Having them as class constants gives a hint to where the request was
     * created and where its custom attributes were added.
     *
     * @var string
     */
    public const ATTR_LAYOUT = 'layout';
    public const ATTR_PROXY = 'proxy';
    public const ATTR_REQUEST_ID = 'request_id';

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
        // All should be set here with defaults to serve as a form of documentation, even if not used by all routes,
        // e.g. `layout` attribute is not used by /api routes but is still documented here.
        $query = $request->getQueryParams();
        $request = $request
            ->withAttribute(
                // Generate unique 50-char ID for each request, e.g. 1670837243.176900-47cde7c7fead438b9a5ec2c6e961e740
                self::ATTR_REQUEST_ID,
                str_pad(microtime(true), 17, '0', STR_PAD_RIGHT) . '-' . bin2hex(random_bytes(16)) // ensure 6-digit μs
            )
            ->withAttribute(
                // Whether to wrap HTML for view in layout template, true by default. See src/Web/view/layout.phtml.
                self::ATTR_LAYOUT,
                intval($query['layout'] ?? 1) // cannot use || as value may be 0
            )
            ->withAttribute(
                // Whether this is a proxy/internal request. See \App\Router::route().
                self::ATTR_PROXY,
                0
            );

        $fallbackHandler = new ErrorController($this->config, $this->logger, $this->router);
        $response = $this->router->process($request, $fallbackHandler);

        $this->send($response);
    }

    /**
     * Send out response to client
     *
     * @param ResponseInterface
     * @return void
     * @throws RuntimeException if headers already sent.
     */
    protected function send(ResponseInterface $response)
    {
        if (headers_sent()) {
            throw new RuntimeException('Headers already sent, response could not be emitted.');
        }

        http_response_code($response->getStatusCode());

        foreach ($response->getHeaders() as $name => $values) {
            header(
                sprintf('%s: %s', $name, $response->getHeaderLine($name)),
                false // header doesn't replace a previous similar header
            );
        }

        echo $response->getBody();
        exit(); // must exit for response to be written properly
    }
}
