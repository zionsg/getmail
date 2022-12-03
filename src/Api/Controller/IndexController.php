<?php

namespace Api\Controller;

use Api\Response;

class IndexController
{
    public function error()
    {
        $response = new Response(404, 'Endpoint not found.');
        $response->send();
    }

    public function index()
    {
        $response = new Response(200, '', [
            'message' => 'Hello World!',
        ]);
        $response->send();
    }
}
