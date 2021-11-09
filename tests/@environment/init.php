<?php

require dirname(dirname(__DIR__)) . '/vendor/autoload.php';

Horizon\Foundation\Services\Environment::set('CONFIG', 'tests/@environment/config');
Horizon\Foundation\Bootstrapper::startTest(dirname(dirname(__DIR__)));
