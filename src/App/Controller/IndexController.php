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
     * Error action
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function errorAction(ServerRequestInterface $request): ResponseInterface
    {
        return new WebResponse($this->config, $this->logger, $request, 404, '', [ // skip view template
            'errorMessage' => 'Invalid page.', // use different error message from \Web\Controller\IndexController
        ]);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function indexAction(ServerRequestInterface $request): ResponseInterface
    {
        return new RedirectResponse('/web', 302);
    }
}
