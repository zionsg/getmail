<?php

namespace App;

use App\Logger;

/**
 * Router class
 */
class Router
{
    /**
     * Route types
     *
     * @var string
     */
    public const LITERAL = 'literal';
    public const REGEX = 'regex';

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
     * @param array $options
     */
    public function __construct(array $options)
    {
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
     * Handle request
     *
     * @return void
     */
    public function handle()
    {
        $uri = $_SERVER['REQUEST_URI'];
        $queryPos = strpos($uri, '?');
        $path = (false === $queryPos) ? $uri : substr($uri, 0, $queryPos); // remove query string portion if any

        $routeOptions = $this->matchRoute($path, $this->routes);
        $controller = $routeOptions['controller'] ?? $this->errorController;
        $action = $routeOptions['action'] ?? $this->errorAction;

        $handler = new $controller();
        $handler->$action();
    }

    /**
     * Match route
     *
     * @param string $path Path relative to domain name without querystring.
     * @param array $routes
     * @return array Route options. Empty array returned if no match is found.
     */
    protected function matchRoute(string $path, array $routes)
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
