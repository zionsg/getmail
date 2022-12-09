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
        /** @property string FQCN (Fully-Qualified Class Name) of controller used if no controller is specified. */
        'error_controller' => \App\Controller\IndexController::class,

        /** @property string Name of method in all controllers for handling errors. */
        'error_action' => 'errorAction',

        /**
         * @property array Routes. Key-value pairs where key is route name and value is array of options for route.
         *     See $routeDefaults in \App\Router on options.
         */
        'routes' => [
            'api' => [
                'type' => Router::LITERAL,
                'route' => '/api',
                'controller' => \Api\Controller\IndexController::class,
                'action' => 'indexAction',
                'child_routes' => [
                    'healthcheck' => [
                        'type' => Router::LITERAL,
                        'route' => '/healthcheck',
                        'controller' => \Api\Controller\SystemController::class,
                        'action' => 'healthcheckAction',
                    ],
                ],
            ],

            'web' => [
                'type' => Router::LITERAL,
                'route' => '/web',
                'controller' => \Web\Controller\IndexController::class,
                'action' => 'indexAction',
            ],

            'root' => [ // this should be the last route as it is a fallback and that "/" matches everything
                'type' => Router::LITERAL,
                'route' => '/',
                'controller' => \App\Controller\IndexController::class,
                'action' => 'indexAction',
            ],
        ],
    ],
];
