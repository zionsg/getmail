<?php

namespace App\Controller;

use App\Controller\AbstractController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Web\Response;

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
        $response = new \Web\Response($this->config, 404, '', [ // deliberate skipping of view template
            'errorMessage' => 'Invalid page.', // use different error message from \Web\Controller\IndexController
        ]);
        $response->send();
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function indexAction(ServerRequestInterface $request)
    {
        $response = new Response($this->config, 302);
        $response->headers[] = 'Location: /web';
        $response->send();
    }
}
