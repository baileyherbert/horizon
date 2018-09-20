<?php

namespace App\Http\Controllers;

use Horizon\Routing\Controller;
use Horizon\Http\Request;
use Horizon\Http\Response;

class Welcome extends Controller
{

    /**
     * Executes the controller, which should take the request and form a response.
     */
    public function __invoke(Request $request, Response $response)
    {
        $response->view('welcome');
    }

}
