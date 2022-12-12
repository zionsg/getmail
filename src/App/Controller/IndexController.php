<?php

namespace App\Controller;

use App\Controller\AbstractController;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Web\WebResponse;

class IndexController extends AbstractController
{
    /**
     * @see AbstractController::handle()
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $uri = $request->getUri()->withPath('/web'); // alter path but retain querystring

        return new RedirectResponse($uri, 302);
    }
}
