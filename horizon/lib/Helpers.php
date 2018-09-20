<?php

use Horizon\Framework\Kernel;
use Horizon\Exception\HorizonException;

// Aliases
class Route extends \Horizon\Routing\RouteFacade {};
class Database extends \Horizon\Database\DatabaseFacade {};
class DB extends \Horizon\Database\DatabaseFacade {};

/**
 * Configuration helper for getting config values.
 *
 * @param string $key
 * @param mixed|null $default
 * @return mixed
 */
function config($key, $default = null)
{
    return Horizon::config($key, $default);
}

/**
 * Renders a view in the current response object.
 *
 * @param string $templateFile
 * @param array $context
 * @return string
 */
function view($templateFile, array $context = array())
{
    $response = Kernel::getResponse();

    if (is_null($response)) {
        throw new HorizonException(0x0008, sprintf('Cannot render template file: %s', $templateFile));
    }

    return $response->view($templateFile, $context);
}

/**
 * Gets or sets the theme name to use for view template rendering.
 * If the first argument $name is provided, sets the theme to that value. Otherwise, returns the current theme.
 *
 * @param string|null $name
 * @return string|null
 */
function theme($name = null)
{
    if (is_null($name)) {
        return Kernel::getTheme();
    }

    Kernel::setTheme($name);
}

/**
 * Translates the specified text using the language bucket from the kernel, replacing the provided variables if
 * applicable. If no translation is available, returns the provided text (with variables still replaced).
 *
 * @param string $text
 * @param array $variables
 * @return string
 */
function __($text, $variables = array())
{
    $bucket = Kernel::getLanguageBucket();
    return $bucket->translate($text, $variables);
}

/**
 * Determines if the integer is an octal.
 *
 * @param int $int
 * @return bool
 */
function is_octal($int)
{
    return decoct(octdec($int)) == $int;
}

/**
 * Terminates the page. Equivalent to die(), but it gives the kernel a chance for any last-minute work.
 */
function terminate()
{
    Kernel::close();
}