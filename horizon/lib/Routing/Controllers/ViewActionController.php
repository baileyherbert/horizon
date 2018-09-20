<?php

namespace Horizon\Routing\Controllers;

use Horizon\Http\Response;


class ViewActionController
{

    public function __invoke(Response $response, $view, $variables)
    {
        $response->view($view, $variables);
    }

}