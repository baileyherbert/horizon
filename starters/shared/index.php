<?php

/**
 * This file starts the application in legacy routing mode.
 *
 * You should take precautions when building a shared application to protect the vendor directory. In this starter,
 * the vendor is inside a directory called 'horizon', but you can change the name to anything.
 */

require_once __DIR__ . '/horizon/vendor/autoload.php';
Horizon\Foundation\Bootstrapper::startWebApplication('legacy', __DIR__);
