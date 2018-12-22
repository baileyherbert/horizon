<?php

use Horizon\Exception\HorizonException;
use Horizon\Framework\Application;

// Facade aliases
class Route extends \Horizon\Routing\RouteFacade {};
class Database extends \Horizon\Database\DatabaseFacade {};
class DB extends \Horizon\Database\DatabaseFacade {};

/**
 * Gets the value of a configuration entry at the specified key path. The path should be in dot notation, with
 * the first segment containing the name of the configuration file. If the file or key path does not exist, the
 * default value is returned.
 *
 * @param string $key
 * @param mixed|null $default
 * @return mixed
 */
function config($key, $default = null)
{
    try {
        return Application::config($key, $default);
    }
    catch (HorizonException $e) {
        return $default;
    }
}

/**
 * Renders a view in the current response object.
 *
 * @param string $templateFile
 * @param array $context
 * @return void
 * @throws HorizonException
 */
function view($templateFile, array $context = array())
{
    $response = Application::kernel()->http()->response();

    if (is_null($response)) {
        throw new HorizonException(0x0008, sprintf('Cannot render template file: %s', $templateFile));
    }

    $response->view($templateFile, $context);
}

/**
 * Redirects the current response object.
 *
 * @param string $to
 * @param int $code
 * @param bool $halt
 * @return void
 * @throws HorizonException
 */
function redirect($to = null, $code = 302, $halt = true)
{
    $response = Application::kernel()->http()->response();

    if (is_null($response)) {
        throw new HorizonException(0x0008, sprintf('Cannot redirect to target: %s', $to));
    }

    if ($halt) {
        $response->halt();
    }

    $response->redirect($to, $code);
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
    $bucket = Application::kernel()->translation()->bucket();
    return $bucket->translate($text, $variables);
}

/**
 * Translates the specified text using the language bucket from the kernel, replacing the provided variables if
 * applicable. If no translation is available, returns the provided text (with variables still replaced).
 *
 * @param string $text
 * @param array $variables
 * @return string
 */
function translate($text, $variables = array())
{
    return __($text, $variables);
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
    Application::kernel()->shutdown();
}
