<?php

namespace Api\Controller;

use Api\ApiResponse;
use App\Controller\AbstractController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class IndexController extends AbstractController
{
    /**
     * Error action
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function errorAction(ServerRequestInterface $request): ResponseInterface
    {
        return new ApiResponse($this->config, $this->logger, $request, 404, 'Endpoint not found.');
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function indexAction(ServerRequestInterface $request): ResponseInterface
    {
        return new ApiResponse($this->config, $this->logger, $request, 200, '', [
            'message' => 'Hello World!',
        ]);
    }
}
