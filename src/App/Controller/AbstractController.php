<?php

namespace App\Controller;

use App\Config;
use App\Router;
use Laminas\Diactoros\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Base controller class
 */
abstract class AbstractController implements RequestHandlerInterface
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
     * @var LoggerInterface
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
     * @param Config $config Application config.
     * @param LoggerInterface $logger Logger.
     * @param Router $router Router. This can be used to call internal routes.
     * @return void
     */
    public function __construct(Config $config, LoggerInterface $logger, Router $router)
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->router = $router;
    }

    /**
     * Error action
     *
     * @see RequestHandlerInterface::handle()
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function errorAction(ServerRequestInterface $request): ResponseInterface
    {
        return new Response();
    }

    /**
     * Handles a request and produces a response.
     *
     * May call other collaborating code to generate the response.
     *
     * Default action. All controller actions follow this method signature.
     *
     * @see RequestHandlerInterface::handle()
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new Response();
    }
}
