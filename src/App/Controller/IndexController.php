<?php

namespace App\Controller;

use Web\Response;

class IndexController
{
    public function error()
    {
        $response = new \Web\Response(404, '', [ // deliberate skipping of view template
            'errorMessage' => 'Invalid page.', // use different error message from \Web\Controller\IndexController
        ]);
        $response->send();
    }

    public function index()
    {
        $response = new Response(302);
        $response->headers[] = 'Location: /web';
        $response->send();
    }
}
