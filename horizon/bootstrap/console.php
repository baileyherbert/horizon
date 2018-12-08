#!/usr/bin/env php
<?php

define('CONSOLE_MODE', true);

require_once dirname(__DIR__) . '/vendor/autoload.php';

Horizon\Framework\Kernel::boot();
