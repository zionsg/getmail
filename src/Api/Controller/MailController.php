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
        foreach ($emailOverviews as $overview) {
            if (! preg_match($subjectRegex, $overview->subject)) {
                continue;
            }

            $mailOverview = $overview;
            break;
        }

        // Fetch plaintext body for mail
        $mailBody = $this->getPart($conn, $mailOverview->uid, 'text/plain');

        // Close connection
        imap_close($conn);

        return new ApiResponse($this->config, $this->logger, $request, 200, '', [
            'mail_body' => $mailBody,
            'mail_overview' => $mailOverview,
        ]);
    }

    /**
     * Get part of mail body corresponding to specified MIME type
     *
     * @link Adapted from https://stackoverflow.com/a/25507756
     * @param IMAP\Connection $imap IMAP stream to mailbox.
     * @param int $uid Message number.
     * @param string $mimetype MIME type.
     * @param null|stdClass Structure of message, as per return value of
     *     imap_fetchstructure().
     * @param int $partNumber A string of integers delimited by period
     *     which index into a body part list as per the IMAP4 specification.
     * @return string
     */
    protected function getPart(
        \IMAP\Connection $imap,
        int $uid,
        string $mimetype,
        \stdClass $structure = null,
        string $partNumber = ''
    ): string {
        if (! $structure) {
            $structure = imap_fetchstructure($imap, $uid, FT_UID);
        }

        if ($structure) {
            if ($mimetype === $this->getMimeType($structure)) {
                if (! $partNumber) {
                    $partNumber = '1';
                }

                $text = imap_fetchbody($imap, $uid, $partNumber, FT_UID | FT_PEEK); // do not mark email as Seen
                switch ($structure->encoding) {
                    // Constants for transfer encoding as per
                    // https://www.php.net/manual/en/function.imap-fetchstructure.php
                    case ENCBASE64:
                        return imap_base64($text);
                    case ENCQUOTEDPRINTABLE:
                        return imap_qprint($text);
                    default:
                        return $text;
                }
            }

            // Multipart
            if (TYPEMULTIPART === $structure->type) {
                foreach ($structure->parts as $index => $subStructure) {
                    $prefix = '';
                    if ($partNumber) {
                        $prefix = $partNumber . '.';
                    }

                    $data = $this->getPart($imap, $uid, $mimetype, $subStructure, $prefix . ($index + 1));
                    if ($data) {
                        return $data;
                    }
                }
            }
        }
        return '';
    }

    /**
     * Get MIME type for structure of mail body
     *
     * @param stdClass Structure of message, as per return value of
     *     imap_fetchstructure().
     * @return string
     */
    protected function getMimeType(\stdClass $structure): string
    {
        // Order as per primary body type in https://www.php.net/manual/en/function.imap-fetchstructure.php
        $primaryBodyType = [
            'text', 'multipart', 'message', 'application', 'audio', 'image', 'video', 'model', 'other',
        ];

        if ($structure->subtype ?? '') {
            return strtolower($primaryBodyType[(int) $structure->type] . '/' . $structure->subtype);
        }

        return 'text/plain';
    }
}
