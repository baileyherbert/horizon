<?php

namespace App;

use Horizon\Routing\Controller;
use Horizon\Http\Response;

class Welcome extends Controller
{

    public function __invoke(Response $response)
    {
        $response->view('start');
    }

}