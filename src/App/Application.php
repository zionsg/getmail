<?php

namespace App;

use App\Config;
use App\Logger;
use Web\Form\IndexForm;
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
        $form = new IndexForm();
        $response = new WebResponse(
            200,
            'index.phtml',
            [
                'form' => $form,
            ],
            true // wrap in layout
        );

        if ('GET' === $_SERVER['REQUEST_METHOD']) {
            $response->send();

            return;
        }

        // POST
        $form->setData($_POST);
        $form->validate();

        // Return invalid form
        if (! $form->isValid) {
            $response->viewData['errorMessage'] = $form->errorMessage ?: 'Form has errors.';
            $response->send();

            return;
        }

        // Open connection to specific mailbox
        $host = Config::get('mail_imap_host');
        $port = Config::get('mail_imap_port');
        $user = Config::get('mail_username');
        $pass = Config::get('mail_password');
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
        $response->viewData['mailOverview'] = json_encode($mailOverview, JSON_PRETTY_PRINT);
        $response->viewData['mailBody'] = $mailBody;
        $response->send();
    }
}
