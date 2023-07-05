<?php

namespace Doc\Controller;

use Doc\DocResponse;
use App\Controller\AbstractController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class IndexController extends AbstractController
{
    /**
     * @see AbstractController::errorAction()
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function errorAction(ServerRequestInterface $request): ResponseInterface
    {
        return new DocResponse($this->config, $this->logger, $request, 404, 'File not found.');
    }

    /**
     * @see AbstractController::handle()
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $matches = $request->getAttribute('matches', []);
        $filePath = realpath(__DIR__ . '/../assets/' . str_replace( // remove .. in user-provided path
            '..',
            '',
            $matches[1] ?? 'invalid-file.xyz'
        ));

        return new DocResponse($this->config, $this->logger, $request, 200, '', $filePath);
    }
}
