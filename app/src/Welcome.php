<?php

namespace App;

use Horizon\Http\Controller;
use Horizon\Http\Response;

class Welcome extends Controller
{

    public function __invoke(Response $response)
    {
        view('start');
    }

}
