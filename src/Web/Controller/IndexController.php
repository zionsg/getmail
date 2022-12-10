<?php

namespace Web\Controller;

use App\Controller\AbstractController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Web\WebResponse;
use Web\Form\IndexForm;

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
        return new WebResponse($this->config, $this->logger, $request, 404, 'error.phtml', [
            'message' => 'Page not found.',
        ]);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function indexAction(ServerRequestInterface $request): ResponseInterface
    {
        $form = new IndexForm($this->config);
        $response = new WebResponse(
            $this->config,
            $this->logger,
            $request,
            200,
            'index.phtml',
            [
                'form' => $form,
            ],
            true // wrap in layout
        );

        if ('GET' === $_SERVER['REQUEST_METHOD']) {
            return $response;
        }

        // POST
        $form->setData($_POST);
        $form->validate();

        // Return invalid form
        if (! $form->isValid) {
            $response->updateViewData([
                'form' => $form,
            ]);

            return $response;
        }

        // Open connection to specific mailbox
        $host = $this->config->get('mail_imap_host');
        $port = $this->config->get('mail_imap_port');
        $user = $this->config->get('mail_username');
        $pass = $this->config->get('mail_password');
        $mailbox = 'INBOX';
        $conn = imap_open(
            "{{$host}:{$port}/imap/ssl}{$mailbox}", // e.g. {imap.gmail.com:993/imap/ssl}INBOX
            $user,
            $pass,
            OP_READONLY
        );

        // Retrieve emails
        $info = imap_check($conn);
        $emailCnt = $info->Nmsgs;
        $emailOverviews = array_reverse(imap_fetch_overview($conn, "1:{$emailCnt}", 0)); // save in descending order

        // Get the most recent email matching a subject and retrieve its body
        $subjectRegex = '/' . $form->fields['subject_pattern']['value'] . '/i';
        $mailOverview = null;
        $mailBody = '';
        foreach ($emailOverviews as $emailOverview) {
            if (! preg_match($subjectRegex, $emailOverview->subject)) {
                continue;
            }

            $mailOverview = $emailOverview;
            $mailBody = imap_body($conn, $emailOverview->uid, FT_UID | FT_PEEK); // do not mark email as Seen
            break;
        }

        // Close connection
        imap_close($conn);

        // Return response
        $form->setData(); // clear form
        $response->updateViewData([
            'form' => $form,
            'mailBody' => $mailBody,
            'mailOverview' => json_encode($mailOverview, JSON_PRETTY_PRINT),
        ]);

        return $response;
    }
}
