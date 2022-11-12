<?php

namespace App;

use App\ApiResponse;
use App\Logger;

/**
 * Main application class
 */
class Application
{
    /**
     * Application configuration
     *
     * @var array
     */
    protected $config = [];

    /**
     * Constructor
     *
     * @param array $config Application configuration.
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;

        Logger::infoLog('Application started.');
    }

    /**
     * Run application
     *
     * @return void
     */
    public function run()
    {
        $host = getenv('GETMAIL_MAIL_IMAP_HOST');
        $port = getenv('GETMAIL_MAIL_IMAP_PORT');
        $user = getenv('GETMAIL_MAIL_USERNAME');
        $pass = getenv('GETMAIL_MAIL_PASSWORD');
        $mailbox = 'INBOX';

        // Open connection to specific mailbox
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
        $subject = 'password';
        $subjectPattern = '/' . $subject . '/i';
        $email = null;
        $body = '';
        foreach ($emailOverviews as $emailOverview) {
            if (!preg_match($subjectPattern, $emailOverview->subject)) {
                continue;
            }

            $email = $emailOverview;
            $body = imap_body($conn, $emailOverview->uid, FT_UID | FT_PEEK); // do not mark email as Seen
            break;
        }

        // Close connection
        imap_close($conn);

        // Return response
        $response = new ApiResponse(200, '', [
            'email' => $email,
            'body' => $body,
        ]);
        $response->send();
    }
}
