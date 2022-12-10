<?php

namespace App\Controller;

use App\Config;
use App\Logger;
use Laminas\Diactoros\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

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
     * @var Logger
     */
    protected $logger = null;

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
