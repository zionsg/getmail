<?php

namespace App;

use RuntimeException;
use App\Config;
use App\Logger;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Router class
 */
class Router implements MiddlewareInterface
{
    /**
     * Route types
     *
     * @var string
     */
    public const LITERAL = 'literal';
    public const REGEX = 'regex';

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
     * @var string
     */
    protected $errorController = '';

    /**
     * @var string
     */
    protected $errorAction = '';

    /**
     * @var array
     */
    protected $routes = [];

    /**
     * Defaults for route options
     *
     * @var array
     * @property string type="literal" Route type as per class constants.
     * @property string route="" String or pattern for route to match.
     * @property string controller="" FQCN (Fully-Qualified Class Name) of controller used for handling route.
     * @property string action="" Name of method in controller for handling route.
     * @property array child_routes=[] Key-value pairs where key is route name and value is array of options for route.
     *     If controller or action is not specified, it will be inherited from the parent route.
     */
    protected $routeDefaults = [
        'type' => self::LITERAL,
        'route' => '',
        'controller' => '',
        'action' => '',
        'child_routes' => [],
    ];

    /**
     * Constructor
     *
     * @param Config $config Application config.
     * @param Logger $logger Logger.
     * @return void
     */
    public function __construct(Config $config, Logger $logger)
    {
        $this->config = $config;
        $this->logger = $logger;

        $options = $this->config->get('router');
        $this->errorController = $options['error_controller'];
        $this->errorAction = $options['error_action'];
        $this->routes = $options['routes'] ?? [];

        // Ensure defaults are set. Max 2 levels for routes.
        foreach ($this->routes as $route => $routeOptions) {
            $this->routes[$route] = array_merge($this->routeDefaults, $routeOptions);

            foreach ($this->routes[$route]['child_routes'] as $childRoute => $childRouteOptions) {
                $this->routes[$route]['child_routes'][$childRoute] = array_merge(
                    $this->routeDefaults,
                    [
                        'controller' => $this->routes[$route]['controller'],
                        'action' => $this->routes[$route]['action'],
                    ],
                    $childRouteOptions
                );
            }
        }
    }

    /**
     * Process an incoming server request
     *
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     *
     * @see MiddlewareInterface::process()
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $routeOptions = $this->matchRoute($path, $this->routes);

        $controllerClass = $routeOptions['controller'] ?? $this->errorController;
        $action = $routeOptions['action'] ?? $this->errorAction;

        $controller = new $controllerClass($this->config, $this->logger, $this); // pass in this Router as last arg
        $response = $controller->$action($request);
        if ($response) {
            $this->send($response);
        } elseif ($handler) {
            $response = $handler($request);
        }

        return $response;
    }

    /**
     * Match route
     *
     * @param string $path Path relative to domain name without querystring.
     * @param array $routes
     * @return array Route options. Empty array returned if no match is found.
     */
    protected function matchRoute(string $path, array $routes): array
    {
        foreach ($routes as $route => $routeOptions) {
            $type = $routeOptions['type'];
            $route = $routeOptions['route'];
            $childRoutes = $routeOptions['child_routes'];

            if (self::LITERAL === $type) {
                if ($path === $route) { // e.g. path is /web, route is /web, child routes skipped cos path has ended
                    return $routeOptions;
                }

                if (0 === strpos($path, $route)) {
                    $childPath = substr($path, strlen($route));
                    if (! $childPath || substr($childPath, 0, 1) !== '/') {
                        // only check child routes of /api if path is /api/something, not /apix or /api.0
                        continue;
                    }

                    $function = __FUNCTION__;
                    $childResult = $this->$function($childPath, $childRoutes);

                    // If no matches for child routes, return parent route with error action and let it handle
                    // so that response type is correct, i.e. application/json for /api/*, text/html for /web/*
                    // E.g. path is /web/abc, parent route is /web, no child routes match, so let parent route handle
                    if ($childResult) {
                        return $childResult;
                    } else {
                        return array_merge(
                            $routeOptions,
                            [
                                'action' => $this->errorAction,
                            ]
                        );
                    }
                }
            }
        }

        return []; // no match found
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
