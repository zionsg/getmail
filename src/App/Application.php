<?php

namespace App;

use App\Config;
use App\Logger;
use Web\Response as WebResponse;

/**
 * Main application class
 */
class Application
{
    /**
     * Constructor
     */
    public function __construct()
    {
        Logger::infoLog('Application started.');
    }

    /**
     * Run application
     *
     * @return void
     */
    public function run()
    {
        $requestId = Helper::makeUniqueId($_SERVER['REQUEST_TIME_FLOAT']);

        $host = Config::get('mail_imap_host');
        $port = Config::get('mail_imap_port');
        $user = Config::get('mail_username');
        $pass = Config::get('mail_password');
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
        $mailOverview = null;
        $mailBody = '';
        foreach ($emailOverviews as $emailOverview) {
            if (! preg_match($subjectPattern, $emailOverview->subject)) {
                continue;
            }

            $mailOverview = $emailOverview;
            $mailBody = imap_body($conn, $emailOverview->uid, FT_UID | FT_PEEK); // do not mark email as Seen
            break;
        }

        // Close connection
        imap_close($conn);

        // Return response
        $response = new WebResponse(
            200,
            'index.phtml',
            [
                'mailOverview' => json_encode($mailOverview, JSON_PRETTY_PRINT),
                'mailBody' => $mailBody,
            ],
            true // wrap in layout
        );
        $response->send();
    }
}
