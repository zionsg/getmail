<?php

/**
 * Routes configuration
 *
 * @var array
 */

use App\Router;

return [
    'router' => [
        'routes' => [
            'root' => [
                'type' => Router::LITERAL,
                'route' => '/',
                'controller' => \App\Controller\IndexController::class,
                'action' => 'index',
            ],

            'api' => [
                'type' => Router::SEGMENT,
                'route' => '/api',
                'controller' => \Api\Controller\IndexController::class,
                'action' => 'index',
                'child_routes' => [
                    'healthcheck' => [
                        'route' => '/healthcheck',
                        'controller' => \Api\Controller\SystemController::class,
                        'action' => 'healthcheck',
                    ],
                ],
            ],

            'web' => [
                'type' => Router::SEGMENT,
                'route' => '/web',
                'controller' => \Web\Controller\IndexController::class,
                'action' => 'index',
            ],
        ],
    ],
];
