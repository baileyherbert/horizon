<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

Horizon\Foundation\Services\Environment::set('HORIZON_MODE', 'web');
Horizon\Foundation\Services\Environment::set('ROUTING_MODE', 'router');
Horizon\Foundation\Application::kernel()->boot();
