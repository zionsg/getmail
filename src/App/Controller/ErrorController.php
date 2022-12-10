<?php

namespace App\Controller;

use App\Controller\AbstractController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Web\WebResponse;

class ErrorController extends AbstractController
{
    /**
     * @see AbstractController::errorAction()
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function errorAction(ServerRequestInterface $request): ResponseInterface
    {
        // Ultimately, this is a web application, hence return HTML response for error
        return new WebResponse($this->config, $this->logger, $request, 404, '', [ // skip view template
            'errorMessage' => 'Invalid page.', // use different error message from \Web\Controller\IndexController
        ]);
    }
}
