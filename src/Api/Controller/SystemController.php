<?php

namespace Api\Controller;

use Api\ApiResponse;
use App\Controller\AbstractController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SystemController extends AbstractController
{
    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function healthcheckAction(ServerRequestInterface $request): ResponseInterface
    {
        return new ApiResponse($this->config, $this->logger, $request, 200, '', [
            'message' => 'OK',
        ]);
    }
}
