<?php

namespace App\Controller;

class ErrorController
{
    public function error()
    {
        $response = new \Web\Response(404, '', [
            'errorMessage' => 'Page not found.',
        ]);
        $response->send();
    }
}
