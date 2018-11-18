<?php

namespace Horizon\Exception;

interface ErrorHandlerInterface
{

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