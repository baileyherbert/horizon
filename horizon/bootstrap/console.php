<?php

if (isset($_SERVER['REMOTE_ADDR'])) {
	echo "Error: Unsupported environment";
	exit(1);
}

require_once dirname(__DIR__) . '/vendor/autoload.php';

Horizon\Foundation\Services\Environment::set('HORIZON_MODE', 'console');
Horizon\Foundation\Application::kernel()->boot();
