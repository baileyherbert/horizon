<?php

namespace Horizon\Routing\Controllers;

use Horizon\Http\Request;
use Horizon\Http\Response;


class RedirectActionController
{

    public function __invoke(Request $request, Response $response, $to, $code)
    {
        $response->redirect($to, $code);
    }

}