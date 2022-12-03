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
    public const SEGMENT = 'segment';
    public const REGEX = 'regex';

    /**
     * @var array
     */
    protected $routes = [];

    /**
     * @var array
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
        if ($routeOptions) {
            $controller = $routeOptions['controller'];
            $action = $routeOptions['action'];
        } else {
            $controller = \App\Controller\ErrorController::class;
            $action = 'error';
        }

        $handler = new $controller();
        $handler->$action();
    }

    /**
     * Match route
     *
     * @param string $path Path relative to domain name without querystring.
     * @param array $routes
     * @return array Route options.
     */
    protected function matchRoute(string $path, array $routes)
    {
        foreach ($routes as $route => $routeOptions) {
            $type = $routeOptions['type'];
            $route = $routeOptions['route'];

            if (self::LITERAL === $type) {
                if ($path === $route) {
                    return $routeOptions;
                }
            } elseif (self::SEGMENT === $type) {
                if (0 === strpos($path, $route)) {
                    $childRoutes = $routeOptions['child_routes'];
                    if (! $childRoutes) {
                        return $routeOptions;
                    }

                    $childPath = substr($path, strlen($route));
                    $function = __FUNCTION__;
                    return $this->$function($childPath, $childRoutes);
                }
            } elseif (self::REGEX === $type) {
                $matches = [];
                if (preg_match($route, $path, $matches)) {
                    return array_merge($routeOptions, [
                        'matches' => $matches,
                    ]);
                }
            } else {
                continue;
            }
        }

        return [];
    }
}
