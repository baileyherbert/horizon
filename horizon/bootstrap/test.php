<?php

// Only allow phpunit to run this file
if (getenv('HORIZON_ENVIRONMENT') !== 'test') die;

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