<?php

namespace Api\Controller;

use Api\Response;

class SystemController
{
    public function healthcheck()
    {
        $response = new Response(200, '', [
            'message' => 'OK',
        ]);
        $response->send();
    }
}
