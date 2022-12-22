<?php

namespace Web\Controller;

use App\Controller\AbstractController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Web\WebResponse;
use Web\Form\MailForm;

class MailController extends AbstractController
{
    /**
     * @see AbstractController::handle()
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $viewPath = 'mail.phtml';
        $form = new MailForm($this->config, $this->logger);

        if ('GET' === $request->getMethod()) {
            return new WebResponse(
                $this->config,
                $this->logger,
                $request,
                200,
                $viewPath,
                [
                    'form' => $form,
                ]
            );
        }

        // POST
        $body = $request->getParsedBody();
        $form->setData($body);
        $form->validate();

        // Return invalid form
        if (! $form->isValid) {
            return new WebResponse(
                $this->config,
                $this->logger,
                $request,
                400,
                $viewPath,
                [
                    'form' => $form,
                ]
            );
        }

        // Call API endpoint
        // Though the API endpoint will validate the submission as well, the form should still do
        // some preliminary validation to catch basic errors and prevent a wasted trip
        $res = $this->router->route($request, '/api/mail', 'POST', $body);
        $resBody = json_decode($res->getBody(), true) ?: [];
        if ($resBody['error']) {
            $form->setError($resBody['error']['message'] ?? 'Error retrieving mail.');

            return new WebResponse(
                $this->config,
                $this->logger,
                $request,
                200,
                $viewPath,
                [
                    'form' => $form,
                ]
            );
        }

        // Clear form
        $form->setData();

        return new WebResponse(
            $this->config,
            $this->logger,
            $request,
            200,
            $viewPath,
            [
                'form' => $form,
                'mailBody' => $resBody['data']['mail_body'],
                'mailOverview' => json_encode($resBody['data']['mail_overview'], JSON_PRETTY_PRINT),
            ]
        );
    }
}
