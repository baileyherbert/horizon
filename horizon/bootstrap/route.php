<?php

define('USE_LEGACY_ROUTING', false);

require_once dirname(__DIR__) . '/vendor/autoload.php';

Horizon\Foundation\Application::kernel()->boot();
