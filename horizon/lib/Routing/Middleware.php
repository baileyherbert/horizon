<?php

namespace Horizon\Routing;

use Horizon\Framework\Application;
use Horizon\Http\Request;
use Horizon\Http\Response;

class Middleware
{

    /**
     * Default execution method for middleware.
     *
     * @return void
     */
    public function __invoke()
    {

    }

    /**
     * Gets the Request instance for this middleware.
     *
     * @return Request
     */
    public function getRequest()
    {
        return Application::kernel()->http()->request();
    }

    /**
     * Gets the Response instance for this middleware.
     *
     * @return Response
     */
    public function getResponse()
    {
        return Application::kernel()->http()->response();
    }

}
