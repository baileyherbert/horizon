<?php

define('USE_LEGACY_ROUTING', true);

require_once dirname(__DIR__) . '/vendor/autoload.php';

Horizon\Foundation\Application::kernel()->boot();
