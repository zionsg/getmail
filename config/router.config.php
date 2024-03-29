<?php

/**
 * Router configuration
 *
 * @var array
 */

use App\Router;

return [
    /** @property array Router configuration. */
    'router' => [
        /** @property string FQCN (Fully-Qualified Class Name) of error controller used if no controller specified. */
        'error_controller' => \App\Controller\ErrorController::class,

        /** @property string Name of method in all controllers for handling errors. */
        'error_action' => 'errorAction',

        /**
         * @property array Routes. Key-value pairs where key is route name and value is array of options for route.
         *     See $routeDefaults in \App\Router on options.
         */
        'routes' => [
            'api' => [
                'type' => Router::ROUTE_LITERAL,
                'route' => '/api',
                'controller' => \Api\Controller\IndexController::class,
                'action' => 'handle',
                'child_routes' => [
                    'healthcheck' => [
                        'type' => Router::ROUTE_LITERAL,
                        'route' => '/healthcheck',
                        'controller' => \Api\Controller\SystemController::class,
                        'action' => 'healthcheckAction',
                    ],
                    'mail' => [
                        'type' => Router::ROUTE_LITERAL,
                        'route' => '/mail',
                        'controller' => \Api\Controller\MailController::class,
                        'action' => 'handle',
                    ],
                ],
            ],

            'doc' => [
                'type' => Router::ROUTE_REGEX,
                'route' => '/doc/([a-z0-9\-]+\.[a-z]+)',
                'controller' => \Doc\Controller\IndexController::class,
                'action' => 'handle',
            ],

            'web' => [
                'type' => Router::ROUTE_LITERAL,
                'route' => '/web',
                'controller' => \Web\Controller\IndexController::class,
                'action' => 'handle',
                'child_routes' => [
                    'mail' => [
                        'type' => Router::ROUTE_LITERAL,
                        'route' => '/mail',
                        'controller' => \Web\Controller\MailController::class,
                        'action' => 'handle',
                    ],
                ],
            ],

            'root' => [ // this should be the last route as it is a fallback and that "/" matches everything
                'type' => Router::ROUTE_LITERAL,
                'route' => '/',
                'controller' => \App\Controller\IndexController::class,
                'action' => 'handle',
            ],
        ],
    ],
];
