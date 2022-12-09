<?php

namespace Api\Controller;

use Api\Response;
use App\Controller\AbstractController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SystemController extends AbstractController
{
    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function healthcheckAction(ServerRequestInterface $request)
    {
        $response = new Response($this->config, 200, '', [
            'message' => 'OK',
        ]);
        $response->send();
    }
}
