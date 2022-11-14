<?php

namespace App;

use App\ApiResponse;
use App\Config;
use App\Logger;

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
