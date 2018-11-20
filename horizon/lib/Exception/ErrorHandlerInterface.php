<?php

namespace Horizon\Exception;

use Horizon\Http\Exception\HttpResponseException;

interface ErrorHandlerInterface
{

    /**
     * Handles an HTTP exception. The default behavior is to show a matching error page.
     *
     * @param HttpResponseException $ex
     * @return void
     */
    public function http(HttpResponseException $ex);

    /**
     * Renders the specified error to the screen. This is only called if error displaying is enabled. An error renderer
     * should not manually halt or kill the page; this will be done automatically by the kernel if the error severity
     * is appropriate.
     *
     * @param HorizonError $error
     * @return void
     */
    public function render(HorizonError $error);

    /**
     * Logs the specified error. This is only called if logging is enabled in the errors configuration file.
     *
     * @param HorizonError $error
     * @return void
     */
    public function log(HorizonError $error);

    /**
     * Reports the specified error.
     *
     * @param HorizonError $error
     * @return void
     */
    public function report(HorizonError $error);

}