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
        $form = new MailForm($this->config, $this->logger);
        $response = new WebResponse(
            $this->config,
            $this->logger,
            $request,
            200,
            'mail.phtml',
            [
                'form' => $form,
            ]
        );

        if ('GET' === $request->getMethod()) {
            return $response;
        }

        // POST
        $body = $request->getParsedBody();
        $form->setData($body);
        $form->validate();

        // Return invalid form
        if (! $form->isValid) {
            $response->updateViewData([
                'form' => $form,
            ]);

            return $response;
        }

        // Call API endpoint
        // Though the API endpoint will validate the submission as well, the form should still do
        // some preliminary validation to catch basic errors and preventing a wasted trip
        $res = $this->router->route($request, '/api/mail', 'POST', $body);
        $resBody = json_decode($res->getBody(), true) ?: [];
        if ($resBody['error']) {
            $form->setError($resBody['error']['message'] ?? 'Error retrieving mail.');
            $response->updateViewData([
                'form' => $form,
            ]);

            return $response;
        }

        // Return response
        $form->setData(); // clear form
        $response->updateViewData([
            'form' => $form,
            'mailBody' => $resBody['data']['mail_body'],
            'mailOverview' => json_encode($resBody['data']['mail_overview'], JSON_PRETTY_PRINT),
        ]);

        return $response;
    }
}
