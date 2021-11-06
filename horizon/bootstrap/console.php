<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

Horizon\Foundation\Services\Environment::set('HORIZON_MODE', 'console');
Horizon\Foundation\Application::kernel()->boot();
