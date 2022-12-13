<?php

namespace Api\Controller;

use Api\ApiResponse;
use App\Controller\AbstractController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class MailController extends AbstractController
{
    /**
     * @see AbstractController::handle()
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() !== 'POST') {
            return new ApiResponse($this->config, $this->logger, $request, 405, 'Method not allowed.');
        }

        // Read only the necessary params as it may be from a form submission which contains other fields
        $body = $request->getParsedBody();
        $subjectPattern = $body['subject_pattern'];
        $apiKey = $body['api_key'];
        $apiToken = $body['api_token'];

        // Validation
        if (strlen($subjectPattern) < 5) {
            return new ApiResponse(
                $this->config,
                $this->logger,
                $request,
                400,
                'Subject pattern must be at least 5 characters.'
            );
        }
        if ($apiKey !== $this->config->get('api_key') || $apiToken !== $this->config->get('api_token')) {
            // Do not let user know which is wrong to prevent guessing attacks
            return new ApiResponse($this->config, $this->logger, $request, 401, 'Invalid credentials.');
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
        $subjectRegex = '/' . $subjectPattern . '/i';
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

        return new ApiResponse($this->config, $this->logger, $request, 200, '', [
            'mail_body' => $mailBody,
            'mail_overview' => $mailOverview,
        ]);
    }
}
