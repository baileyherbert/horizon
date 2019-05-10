<?php

namespace Horizon\Routing\Controllers;

use Horizon\Http\Response;
use Horizon\Http\Controller;

class ViewActionController extends Controller
{

    public function __invoke(Response $response, $view, $variables)
    {
        $response->view($view, $variables);
    }

}
