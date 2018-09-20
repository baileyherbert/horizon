<?php

namespace Horizon\Http;

class MiniRequest extends Request
{

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
    public function __construct(array $query = array(), array $request = array(), array $attributes = array(), array $cookies = array(), array $files = array(), array $server = array(), $content = null, $uri)
    {
        parent::__construct($query, $request, $attributes, $cookies, $files, $server, $content);
        $this->simpleUri = $uri;
    }

    public static function simple($uri)
    {
        return new self(array(), array(), array(), array(), array(), $_SERVER, null, $uri);
    }

    public function path()
    {
        return $this->simpleUri;
    }

    public function isLegacyRoutingAllowed()
    {
        return false;
    }

}