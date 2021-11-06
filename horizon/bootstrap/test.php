<?php

if (getenv('HORIZON_MODE') !== 'test') die;

// Get autoloader paths
$horizonVendor = dirname(__DIR__) . '/vendor/autoload.php';
$appVendor = dirname(dirname(__DIR__)) . '/app/vendor/autoload.php';

// Require horizon/vendor
if (file_exists($horizonVendor)) {
	require $horizonVendor;
}

// Require app/vendor
if (file_exists($appVendor)) {
	require $appVendor;
}

// Force development mode
Horizon\Foundation\Services\Environment::set('APP_MODE', 'development');
