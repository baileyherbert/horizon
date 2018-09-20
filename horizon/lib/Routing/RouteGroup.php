<?php

namespace Horizon\Routing;

class RouteGroup
{

    /**
     * An array of properties the group will apply to child routes.
     *
     * @var array
     */
    protected $properties = array();

    /**
     * The parent group, or null.
     *
     * @var RouteGroup
     */
    protected $parent;

    /**
     * Constructs a new RouteGroup instance.
     *
     * @param array $properties
     * @param RouteGroup $parentGroup
     */
    public function __construct(array $properties, RouteGroup $parentGroup = null)
    {
        $this->properties = $properties;
        $this->parent = $parentGroup;
    }

    /**
     * Gets a property from the route group.
     *
     * @param string $name
     * @param mixed $default
     * @return mixed or $default
     */
    protected function property($name, $default = null)
    {
        if (isset($this->properties[$name])) {
            return $this->properties[$name];
        }

        return $default;
    }

    /**
     * Sets the prefix of the group. This does not modify the prefix of routes already added to the group, but will
     * affect routes added afterwards.
     */
    public function setPrefix($prefix = null)
    {
        $this->properties['prefix'] = $prefix;
    }

    /**
     * Sets the namespace of the group. This does not modify the namespace of routes already added to the group, but will
     * affect routes added afterwards.
     */
    public function setNamespace($namespace = null)
    {
        $this->properties['namespace'] = $namespace;
    }

    /**
     * Sets the name prefix of the group. This does not modify the name of routes already added to the group, but will
     * affect routes added afterwards.
     */
    public function setName($name = null)
    {
        $this->properties['name'] = $name;
    }

    /**
     * Sets the domain of the group. This does not modify the name of routes already added to the group, but will
     * affect routes added afterwards.
     */
    public function setDomain($domain = null)
    {
        $this->properties['domain'] = $domain;
    }

    /**
     * Adds a middleware to the route group. This does not modify the prefix of routes already added to the group, but
     * will affect routes added afterwards.
     *
     * @param string[] $middleware
     */
    public function addMiddleware(array $middleware)
    {
        if (!isset($this->properties['middleware'])) {
            $this->properties['middleware'] = array();
        }

        foreach ($middleware as $name) {
            if (in_array($name, $this->properties['middleware'])) continue;

            $this->properties['middleware'][] = $name;
        }
    }

    /**
     * Gets the group's prefix if it has one set, including parent groups' prefixes. If the $uri parameter is supplied,
     * the prefix is applied to the uri and returned.
     *
     * @param string|null $uri
     * @return string
     */
    public function prefix($uri = null)
    {
        $parents = $this->parent ? $this->parent->prefix() : '';

        if (!is_null($uri)) {
            return $parents . $this->property('prefix', '') . $uri;
        }

        return $parents . $this->property('prefix', '');
    }

    /**
     * Gets the group's name prefix if it has one set, including parent groups' prefixes. If the $name parameter is supplied,
     * the prefix is applied to the provided name and returned.
     *
     * @param string|null $name
     * @return string
     */
    public function namePrefix($name = null)
    {
        $parents = $this->parent ? $this->parent->namePrefix() : '';

        if (!is_null($name)) {
            return $parents . $this->property('name', '') . $name;
        }

        return $parents . $this->property('name', '');
    }

    /**
     * Gets the group's domain if it has one set, overriding parent domains.
     */
    public function domain()
    {
        $parent = $this->parent ? $this->parent->domain() : null;
        $self = $this->property('domain', null);

        if (!is_null($self)) {
            return $self;
        }

        return $parent;
    }

    /**
     * Gets the group's namespace prefix if it has one set, including parent groups' prefixes. If the $namespace parameter
     * is supplied, the prefix is applied to the provided namespace and returned.
     *
     * @param string|null $name
     * @return string
     */
    public function namespacePrefix($namespace = null)
    {
        $parents = $this->parent ? $this->parent->namespacePrefix() : '';

        if (!is_null($namespace)) {
            return $parents . $this->property('namespace', '') . $namespace;
        }

        return $parents . $this->property('namespace', '');
    }

    /**
     * Gets the group's middleware if it has any set, including parent groups' middleware. If the $existing parameter
     * is supplied, the middleware arrays are joined with duplicates removed.
     *
     * @param array $existing
     * @return array
     */
    public function middleware($existing = array())
    {
        $parentMiddleware = $this->parent ? $this->parent->middleware() : array();
        $thisMiddleware = $this->property('middleware', array());

        // Add parent middleware
        foreach ($parentMiddleware as $middleware) {
            if (!in_array($middleware, $existing)) {
                $existing[] = $middleware;
            }
        }

        // Add self middleware
        foreach ($thisMiddleware as $middleware) {
            if (!in_array($middleware, $existing)) {
                $existing[] = $middleware;
            }
        }

        return $existing;
    }

}