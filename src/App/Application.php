<?php

namespace App;

use App\Config;
use App\Logger;
use App\Router;

/**
 * Main application class
 */
class Application
{
    /**
     * Constructor
     */
    public function __construct()
    {
        Logger::infoLog('Application started.');
    }

    /**
     * Run application
     *
     * @return void
     */
    public function run()
    {
        $router = new Router(Config::get('router', []));
        $router->handle();
    }
}
