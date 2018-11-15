<?php

namespace Horizon\Routing;

use Horizon\Framework\Kernel;
use Horizon\Http\Request;
use Horizon\Http\Response;

class Controller
{

    /**
     * Get the middleware assigned to the controller. This is in excess to middleware defined at the router level.
     *
     * @return array
     */
    public function getMiddleware()
    {
        return array();
    }

    /**
     * Gets the Request instance for this controller.
     *
     * @return Request
     */
    public function getRequest()
    {
        return Kernel::getRequest();
    }

    /**
     * Gets the Response instance for this controller.
     *
     * @return Response
     */
    public function getResponse()
    {
        return Kernel::getResponse();
    }

}