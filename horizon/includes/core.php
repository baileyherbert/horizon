<?php

if (version_compare(phpversion(), '5.4.0', '<')) {
    http_response_code(500);

    if (file_exists($errorFile = dirname(__DIR__) . '/errors/000.html')) {
        $contents = file_get_contents($errorFile);
        $contents = str_replace('%current_version%', phpversion(), $contents);
        $contents = str_replace('%minimum_version%', '5.4.0', $contents);

        echo $contents;
        die;
    }

    die("Your current PHP version (" . phpversion() . ") is not supported by the Horizon Framework. Please upgrade to at least PHP 5.4 to run this application.");
}
