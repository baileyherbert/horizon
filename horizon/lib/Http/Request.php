<?php

namespace Horizon\Http;

use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\ParameterBag;
use Horizon\Http\Cookie\Session;
use Horizon\Routing\Route;

class Request extends SymfonyRequest
{

    use Traits\HasHttpCookies,
        Traits\HasHttpInput,
        Traits\HasJsonInput,
        Traits\HasFlashing;

    /**
     * @var Route
     */
    protected $horizonRoute;

    /**
     * @var ParameterBag
     */
    protected $horizonAttributes;

    /**
     * Constructs a new Request instance.
     *
     * @param array $query
     * @param array $request
     * @param array $attributes
     * @param array $cookies
     * @param array $files
     * @param array $server The SERVER parameters.
     * @param string|null $content The raw body data.
     */
    public function __construct(array $query = array(), array $request = array(), array $attributes = array(), array $cookies = array(), array $files = array(), array $server = array(), $content = null)
    {
        parent::__construct($query, $request, $attributes, $cookies, $files, $server, $content);

        $this->horizonAttributes = new ParameterBag();
        $this->overrideGlobals();
    }

    /**
     * Automatically creates a Request instance from the current environment.
     */
    public static function auto()
    {
        static::injectQueryArgs();

        return new self($_GET, $_POST, array(), $_COOKIE, $_FILES, $_SERVER, file_get_contents('php://input'));
    }

    /**
     * Gets the path for the request. This always starts with a preceding forward slash (/).
     * Example: "/path/to/file"
     *
     * @return string
     */
    public function path()
    {
        return strtok($this->getRequestUri(), '?');
    }

    /**
     * Gets the absolute URL for the request without query parameters.
     * Example: "https://www.domain.com/path/to/file"
     *
     * @param string|null $path
     * @return string
     */
    public function url($path = null)
    {
        if ($path) {
            return preg_replace('/\?.*/', '', $this->getUriForPath('/' . ltrim($path, '/')));
        }

        return preg_replace('/\?.*/', '', $this->getUri());
    }

    /**
     * Gets the absolute URL for the request, including encoded query parameters.
     * Example: "https://www.domain.com/path/to/file?redirect=%2Fhome"
     *
     * @param string|null $path
     * @return string
     */
    public function fullUrl($path = null)
    {
        $query = $this->getQueryString();
        $uri = $this->url($path);

        if ($path) {
            if (strpos($path, '?') >= 0) {
                $query = substr($path, strpos($path, '?') + 1);
            }
        }

        return $uri . ($query ? '?'.$query : '');
    }

    /**
     * Gets the root URL including the scheme and hostname. This always ends with a trailing slash ('/').
     * Example: "https://www.domain.com/"
     *
     * @return string
     */
    public function root()
    {
        return rtrim($this->getSchemeAndHttpHost() . $this->getBaseUrl(), '/') . '/';
    }

    /**
     * Checks whether this request was initiated from AJAX.
     *
     * @return bool
     */
    public function ajax()
    {
        return $this->isXmlHttpRequest();
    }

    /**
     * Checks whether this request is being transmitted over HTTPS.
     *
     * @return bool
     */
    public function secure()
    {
        return $this->isSecure();
    }

    /**
     * Gets the IP address of the remote client. Can be in IPv4 or IPv6 format depending on server configuration
     * or the remote client's connection.
     *
     * @return string
     */
    public function ip()
    {
        return $this->getClientIp();
    }

    /**
     * Gets the user agent header sent from the client.
     *
     * @return string
     */
    public function userAgent()
    {
        return $this->headers->get('User-Agent');
    }

    /**
     * Gets the value of the specified header as a string.
     *
     * @param string|null $key
     * @return string
     */
    public function header($key = null)
    {
        if (is_null($key)) {
            return $this->headers->all();
        }

        return $this->headers->get($key);
    }

    /**
     * Gets the Route instance matched to the request, or null.
     *
     * @return Route|null
     */
    public function route()
    {
        return $this->horizonRoute;
    }

    /**
     * @see route()
     * @return Route|null
     */
    public function getRoute()
    {
        return $this->route();
    }

    /**
     * Binds a route to and from the request, and copies query parameters from the route.
     *
     * @param Route $route
     */
    public function bind(Route $route)
    {
        $this->horizonRoute = $route;
        $this->horizonRoute->bind($this);

        // Copy queries from the route
        foreach ($route->parameters() as $key => $value) {
            $this->query->set($key, $value);
            $_GET[$key] = $value;
        }
    }

    /**
     * Extracts arguments from the REQUEST_URI into the GET global.
     */
    private static function injectQueryArgs()
    {
        if (!USE_LEGACY_ROUTING) {
            $uri = $_SERVER['REQUEST_URI'];

            if (strpos($uri, '?') !== false) {
                list($path, $query) = explode('?', $uri, 2);
                parse_str($query, $output);

                foreach ($output as $key => $value) {
                    $_GET[$key] = $value;
                }
            }
        }
    }

    /**
     * Sets an attribute, which is useful for sending data from middleware to controllers.
     *
     * @param string $key
     * @param mixed $value
     */
    public function setAttribute($key, $value)
    {
        $this->attributes->set($key, $value);
    }

    /**
     * Gets an attribute, or returns the default value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getAttribute($key, $default = null)
    {
        return $this->attributes->get($key, $default);
    }

    /**
     * Gets whether this request can be routed in legacy format. This is used internally.
     *
     * @return bool
     */
    public function isLegacyRoutingAllowed()
    {
        return true;
    }

}
