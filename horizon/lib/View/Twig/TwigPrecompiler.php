<?php

namespace Horizon\View\Twig;

use Horizon\Framework\Kernel;

use Horizon\Utils\Path;
use Horizon\Utils\TimeProfiler;

use Horizon\Http\MiniRequest;
use Horizon\Routing\RouteLoader;
use Horizon\Routing\RouteParameterBinder;

use Horizon\Extend\Extension;

class TwigPrecompiler
{

    /**
     * @var Extension
     */
    private $extension;
    private $translateNamespaces = array();
    private $templateFileName;

    /**
     * Constructs a new precompiler instance, optionally binding the template to an extension.
     *
     * @param Extension $extension
     */
    public function __construct(Extension $extension = null)
    {
        $this->extension = $extension;
    }

    /**
     * Prepares the string for the Twig compiler by compiling Horizon statements.
     *
     * @param string $value
     * @param string $templateFileName
     * @return string
     */
    public function precompile($value, $templateFileName = null)
    {
        $this->templateFileName = $templateFileName;

        $value = $this->compileStatements($value);
        $translated = (new TwigTranslator())->compile($value, $this->translateNamespaces);

        return ltrim($translated);
    }

    /**
     * Compiles all statements in the string.
     *
     * @param string $value
     * @return string
     */
    public function compileStatements($value)
    {
        return preg_replace_callback(
            '/\B@(@?\w+(?:::\w+)?)([ \t]*)(\( ( (?>[^()]+) | (?3) )* \)[;]*)?/x', function ($match) {
                return $this->compileStatement($match);
            }, $value
        );
    }

    /**
     * Compiles a statement.
     *
     * @param array $match
     * @return string
     */
    public function compileStatement($match)
    {
        if (count($match) < 2) {
            return $match[0];
        }

        // Get arguments and method
        $method = 'tag' . $match[1];
        $arguments = (count($match) >= 4) ? $this->parseArguments($match[3]) : array();

        // Return the compiled value
        if (method_exists($this, $method)) {
            return call_user_func_array(array($this, $method), $arguments);
        }
        else {
            return $match[0];
        }
    }

    /**
     * Parses arguments in the form of ('value', 'value') and returns an array of values.
     *
     * @param string $value
     * @return array
     */
    protected function parseArguments($value)
    {
        $value = rtrim($value, ';');

        if (substr($value, 0, 1) != "(") return array();
        if (substr($value, -1) != ")") return array();

        $value = trim($value, '()');
        $parameters = explode(',', $value);
        $values = array();

        foreach ($parameters as $param) {
            $param = trim($param);
            $parsed = $this->parseValue($param);

            if (!empty($parsed)) {
                $values[] = $parsed;
            }
        }

        return $values;
    }

    /**
     * Parses the provided value to remove syntax characters.
     *
     * @param string $value
     * @return mixed
     */
    protected function parseValue($value)
    {
        if (substr($value, 0, 1) == "'" || substr($value, 0, 1) == "\"") {
            return sprintf('%s', trim($value, "'\""));
        }

        if (strcasecmp('true', $value) == 0 || strcasecmp('false', $value) == 0) {
            return strtolower($value) == 'true';
        }

        if (is_numeric($value))
        {
            if (strpos('.', $value) !== false) {
                return (double) $value;
            }
            else {
                return (int) $value;
            }
        }

        return $value;
    }

    /**
     * Converts a value into a Twig-ready function or filter argument.
     *
     * @param mixed $value
     * @return string
     */
    protected function makeArgument($value)
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_bool($value)) {
            return $value ? "true" : "false";
        }

        return (string) $value;
    }

    /**
     * Converts an array of arguments into a comma-separated Twig-ready list.
     *
     * @param array $values
     * @return string
     */
    protected function makeArguments(array $values)
    {
        $args = '';
        $made = array();

        foreach ($values as $val) {
            $made[] = $this->makeArgument($val);
        }

        $args .= implode(', ', $made);

        return $args;
    }

    public function tagCsrf()
    {
        return '<input type="hidden" name="csrf" value="{{ csrf() | e(\'html_attr\') }}">';
    }

    public function tagTranslate($namespace)
    {
        $this->translateNamespaces[] = $namespace;

        return '';
    }

    public function tag__($text)
    {
        $text = preg_replace("/(?<!\\\\)\"/", "\"", $text);

        return '{{ __("' . $text . '") }}';
    }

    public function tagLink($toPath)
    {
        $request = Kernel::getRequest();
        $currentPath = $request->path();

        if (USE_LEGACY_ROUTING) {
            $request = MiniRequest::simple($toPath);
            $router = RouteLoader::getRouter();
            $route = $router->match($request);

            if ($route !== null) {
                if ($route->fallback() !== null) {
                    $parameters = (new RouteParameterBinder($route))->bind($request);
                    $toPath = $route->fallback();

                    if (!empty($parameters)) {
                        $toPath .= '?' . http_build_query($parameters);
                    }
                }
            }
        }

        return Path::getRelative($currentPath, $toPath, $_SERVER['SUBDIRECTORY']);
    }

    public function tagPublic($relativePath, $scope = null)
    {
        $request = Kernel::getRequest();
        $currentPath = $request->path();
        $root = rtrim(Path::getRelative($currentPath, '/', $_SERVER['SUBDIRECTORY']), '/');

        if ($scope == 'this' || $scope == 'ext' || $scope == 'extension') {
            if (!is_null($this->extension)) {
                $relative = ltrim($relativePath, '/');
                $publicPathLegacy = sprintf('%s/%s', $root, ltrim($this->extension->getMappedLegacyRoute($relative), '/'));
                $publicPathRouted = sprintf('%s/%s', $root, ltrim($this->extension->getMappedPublicRoute($relative), '/'));

                return (USE_LEGACY_ROUTING) ? $publicPathLegacy : $publicPathRouted;
            }
        }

        if (USE_LEGACY_ROUTING) {
            return $root . '/app/public/' . ltrim($relativePath, '/');
        }

        return $root . '/' . ltrim($relativePath, '/');
    }

    public function tagImage($relativePath, $scope = null)
    {
        return $this->tagPublic('/images/' . ltrim($relativePath, '/'), $scope);
    }

    public function tagFile($relativePath, $scope = null)
    {
        return $this->tagPublic('/files/' . ltrim($relativePath, '/'), $scope);
    }

    public function tagScript($relativePath, $scope = null)
    {
        return $this->tagPublic('/scripts/' . ltrim($relativePath, '/'), $scope);
    }

    public function tagStyle($relativePath, $scope = null)
    {
        return $this->tagPublic('/styles/' . ltrim($relativePath, '/'), $scope);
    }

    public function tagRuntime()
    {
        return '{{ runtime() }}';
    }

    public function tagRelative($path)
    {
        if (!is_null($this->templateFileName)) {
            $basePath = dirname($this->templateFileName) . '/';
            return sprintf('"%s%s"', $basePath, $path);
        }

        return $path;
    }

    public function tagRel($path)
    {
        return $this->relative($path);
    }

    public function tagInclude($template)
    {
        return "{% include '$template.twig' %}";
    }

    public function tagExtend($template)
    {
        return "{% extends '$template.twig' %}";
    }

    public function tagSection($name)
    {
        return "{% block $name %}";
    }

    public function tagEndSection()
    {
        return '{% endblock %}';
    }

}
