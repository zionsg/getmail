<?php

namespace App;

use App\Config;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

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
    public const ROUTE_LITERAL = 'literal';
    public const ROUTE_REGEX = 'regex';

    /**
     * Application config
     *
     * @var Config
     */
    protected $config = null;

    /**
     * Logger
     *
     * @var LoggerInterface
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
     * @property string type="literal" Route type as per ROUTE_* constants.
     * @property string route="" Path string or pattern for route to match.
     * @property string controller="" FQCN (Fully-Qualified Class Name) of controller used for handling route.
     * @property string action="" Name of method in controller for handling route.
     * @property array child_routes=[] Key-value pairs where key is route name and value is array of options for route.
     *     If controller or action is not specified, it will be inherited from the parent route.
     */
    protected $routeDefaults = [
        'type' => self::ROUTE_LITERAL,
        'route' => '',
        'controller' => '',
        'action' => '',
        'child_routes' => [],
    ];

    /**
     * Constructor
     *
     * @param Config $config Application config.
     * @param LoggerInterface $logger Logger.
     * @return void
     */
    public function __construct(Config $config, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->logger = $logger;

        $options = $this->config->get('router');
        $this->errorController = $options['error_controller'];
        $this->errorAction = $options['error_action'];
        $this->routes = $options['routes'] ?? [];

        // Ensure defaults are set. Max 2 levels for routes.
        foreach ($this->routes as $routeName => $routeOptions) {
            $this->routes[$routeName] = array_merge($this->routeDefaults, $routeOptions);

            foreach ($this->routes[$routeName]['child_routes'] as $childRouteName => $childRouteOptions) {
                $this->routes[$routeName]['child_routes'][$childRouteName] = array_merge(
                    $this->routeDefaults,
                    [
                        'controller' => $this->routes[$routeName]['controller'],
                        'action' => $this->routes[$routeName]['action'],
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
        if (! $response) {
            $response = $handler($request);
        }

        // Cannot send out response to client here (which will terminate the current script) as
        // controller actions may call this method via route() to get response from internal routes.
        // Let the caller of this method be responsible for the sending.
        return $response;
    }

    /**
     * Call route
     *
     * This is used by controller actions to call other internal routes,
     * e.g. controller action for /web/login route (the caller) uses this method
     * to call /api/authenticate route (the callee) internally to verify
     * credentials instead of duplicating the verification logic. It also saves
     * the hassle of the controller action having to create a new request and
     * calling process().
     *
     * @param ServerRequestInterface $callerRequest Request passed to handler
     *     for caller.
     * @param string $path Path relative to domain name without querystring,
     *     e.g. /api/healthcheck.
     * @param string $method="GET" HTTP method, either GET or POST.
     * @param array $data=[] Body to send to callee, typically for POST.
     * @returns ResponseInterface
     */
    public function route(
        ServerRequestInterface $callerRequest,
        string $path,
        string $method = 'GET',
        array $data = []
    ) {
        // Clone request and change path, allowing original client info such as
        // request ID to be carried over and help to group the requests in an
        // audit trail.
        $uri = $callerRequest->getUri()->withPath($path);
        $request = $callerRequest
            ->withUri($uri)
            ->withMethod($method)
            ->withParsedBody($data)
            ->withAttribute('proxy', 1); // indicate that this is a proxy request

        // Pass in this Router as last arg
        $fallbackHandler = new $this->errorController($this->config, $this->logger, $this);

        return $this->process($request, $fallbackHandler);
    }

    /**
     * Match route
     *
     * @param string $path Path relative to domain name without querystring,
     *     e.g. /api/healthcheck.
     * @param array $routes
     * @return array Route options for matched route. Empty array is returned if
     *     no match is found.
     */
    protected function matchRoute(string $path, array $routes): array
    {
        foreach ($routes as $route => $routeOptions) {
            $type = $routeOptions['type'];
            $route = $routeOptions['route'];
            $childRoutes = $routeOptions['child_routes'];

            if (self::ROUTE_LITERAL === $type) {
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
}
