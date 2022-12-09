<?php

namespace Api\Controller;

use Api\Response;
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
    public function errorAction(ServerRequestInterface $request)
    {
        $response = new Response($this->config, 404, 'Endpoint not found.');
        $response->send();
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function indexAction(ServerRequestInterface $request)
    {
        $response = new Response($this->config, 200, '', [
            'message' => 'Hello World!',
        ]);
        $response->send();
    }
}
