<?php

use Horizon\Framework\Application;

if (!function_exists('app_path')) {
    /**
     * Gets the absolute path to the /app/ directory.
     *
     * @return string
     */
    function app_path()
    {
        return Application::path('/app');
    }
}

if (!function_exists('base_path')) {
    /**
     * Gets the absolute path to the project's root directory.
     *
     * @return string
     */
    function base_path()
    {
        return Application::path();
    }
}

if (!function_exists('config_path')) {
    /**
     * Gets the absolute path to the /app/config/ directory.
     *
     * @return string
     */
    function config_path()
    {
        return Application::path('/app/config');
    }
}

if (!function_exists('public_path')) {
    /**
     * Gets the absolute path to the /app/public/ directory.
     *
     * @return string
     */
    function public_path()
    {
        return Application::path('/app/public');
    }
}
