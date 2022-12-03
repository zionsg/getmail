<?php

namespace Api\Controller;

use Api\Response;

class IndexController
{
    public function index()
    {
        $response = new Response(404, 'Route not found.');
        $response->send();
    }
}
