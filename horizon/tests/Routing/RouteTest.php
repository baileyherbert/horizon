<?php

use PHPUnit\Framework\TestCase;

use Horizon\Routing\Router;
use Horizon\Routing\Route;
use Horizon\Http\Request;

define('USE_LEGACY_ROUTING', false);

class RouteTest extends TestCase
{

    /**
     * @var Router
     */
    private $router;

    /**
     * @var Route
     */
    private $route;

    /**
     * Sets up a basic router and a complete route for testing.
     */
    public function setUp()
    {
        if (is_null($this->router)) {
            $this->router = new Router();
            $this->route = $this->router->createAnyRoute('/{var}', function() {

            })->name('test')->fallback('index.php')->where('var', '[a-zA-Z]+')->defaults('test', true);
        }
    }

    /**
     * Tests that the returned methods are correct.
     */
    public function testMethods()
    {
        $this->assertEquals(Router::$verbs, $this->route->methods());
    }

    /**
     * Tests that the returned uri is correct.
     */
    public function testUri()
    {
        $this->assertEquals('/{var}', $this->route->uri());
    }

    /**
     * Tests that the returned name is correct.
     */
    public function testName()
    {
        $this->assertEquals('test', $this->route->name());
    }

    /**
     * Tests that the fallback path is valid and properly formatted.
     */
    public function testFallback()
    {
        $this->assertEquals('/index.php', $this->route->fallback());
    }

    /**
     * Tests that parameter conditions are stored properly.
     */
    public function testWhere()
    {
        $this->assertEquals(array('var' => '[a-zA-Z]+'), $this->route->wheres);
    }

    /**
     * Tests that default parameter values are stored properly.
     */
    public function testDefaults()
    {
        $this->assertTrue($this->route->getDefault('test'));
    }

    /**
     * Tests that routes correctly match to a corresponding request.
     */
    public function testMatching()
    {
        // Variable {var} should work for [a-zA-Z]
        $validRequest = new Request(array(), array(), array(), array(), array(), array(
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/test'
        ));

        // Variable {var} should fail for [^a-zA-Z]
        $invalidRequest = new Request(array(), array(), array(), array(), array(), array(
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/test123'
        ));

        $this->assertTrue($this->route->matches($validRequest));
        $this->assertFalse($this->route->matches($invalidRequest));
    }

    /**
     * Tests that routes properly bind to requests and generate parameter values.
     */
    public function testBinding()
    {
        $request = new Request(array(), array(), array(), array(), array(), array(
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/test'
        ));

        $request->bind($this->route);

        $this->assertEquals('test', $this->route->parameter('var'));
    }

    /**
     * Tests that optional route parameter values inherit default values when not set.
     */
    public function testBindingDefaults()
    {
        // Create a route with an optional variable that has a default
        $route = $this->router->createAnyRoute('/{var}/{optional?}', function() {

        })->defaults('optional', 'default');

        // Create a request which does not invoke the optional variable
        $request1 = new Request(array(), array(), array(), array(), array(), array(
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/test'
        ));

        // Create a request which does invoke the optional variable
        $request2 = new Request(array(), array(), array(), array(), array(), array(
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/test/overwrite'
        ));

        // Test binding to non-invoking request
        $request1->bind($route);
        $this->assertEquals('test', $route->parameter('var'));
        $this->assertEquals('default', $route->parameter('optional'));

        // Test binding to invoking request
        $request2->bind($route);
        $this->assertEquals('test', $route->parameter('var'));
        $this->assertEquals('overwrite', $route->parameter('optional'));
    }

    /**
     * Tests that parameters in the hostname (domain) function properly.
     */
    public function testDomainParameters()
    {
        // Set the domain globally
        $this->router->createDomain('{subdomain}.test.com');

        // Create test routes
        $route = $this->router->createAnyRoute('/', function() {});

        // Create a request which has a subdomain
        $request1 = new Request(array(), array(), array(), array(), array(), array(
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'SERVER_NAME' => 'www.test.com'
        ));

        // Create a request which does not have a subdomain
        $request2 = new Request(array(), array(), array(), array(), array(), array(
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'SERVER_NAME' => 'test.com'
        ));

        // Test binding to non-invoking request
        $request1->bind($route);
        $this->assertEquals('www', $route->parameter('subdomain'));

        // Test binding to invoking request
        $request2->bind($route);
        $this->assertNull($route->parameter('subdomain'));

        // Variables in the hostname are always required
        $this->assertTrue($route->matches($request1));
        $this->assertFalse($route->matches($request2));
    }

}