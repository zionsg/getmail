<?php

namespace App\Controller;

class IndexController
{
    public function index()
    {
        $response = new \Web\Response(302);
        $response->headers[] = 'Location: /web';
        $response->send();
    }
}
