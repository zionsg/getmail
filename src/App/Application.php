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
        $response = new WebResponse(
            200,
            'index.phtml',
            [],
            true // wrap in layout
        );

        if ('GET' === $_SERVER['REQUEST_METHOD']) {
            $response->send();

            return;
        }

        // POST
        $errorMessage = '';
        $formFields = ['subject_pattern', 'api_key', 'api_token'];
        $formData = [];
        $formErrors = [];
        $isFormValid = true;
        foreach ($formFields as $field) {
            $formData[$field] = trim($_POST[$field] ?? '');
            if (! $formData[$field]) {
                $isFormValid = false;
                $errorMessage = "Field \"{$field}\" cannot be empty.";
                $formErrors[$field] = $errorMessage;
            }
        }

        // Check credentials
        if ($isFormValid) {
            foreach (['api_key', 'api_token'] as $field) {
                if ($formData[$field] !== Config::get($field)) {
                    // Do not set form error for field else hacker will know which is wrong
                    $isFormValid = false;
                    $errorMessage = 'Invalid API credentials';
                    break;
                }
            }
        }

        // Return invalid form
        $response->viewData['isFormValid'] = $isFormValid;
        if (! $isFormValid) {
            $response->viewData['errorMessage'] = $errorMessage ?: 'Form has errors.';
            $response->viewData['formErrors'] = $formErrors;
            $response->viewData['formData'] = $formData;
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
        $subjectRegex = '/' . $formData['subject_pattern'] . '/i';
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
        $response->viewData['mailOverview'] = json_encode($mailOverview, JSON_PRETTY_PRINT);
        $response->viewData['mailBody'] = $mailBody;
        $response->send();
    }
}
