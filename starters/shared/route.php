<?php

/**
 * This file starts the application in modern routing mode. You should configure your webserver's rewrite rules to
 * send requests here.
 */

require_once __DIR__ . '/horizon/vendor/autoload.php';
Horizon\Foundation\Bootstrapper::startWebApplication('router', __DIR__);
