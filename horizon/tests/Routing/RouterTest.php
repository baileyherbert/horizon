<?php

use PHPUnit\Framework\TestCase;
use Horizon\Routing\Route;
use Horizon\Routing\Router;

class RouterTest extends TestCase
{

    /**
     * @var Router
     */
    protected $router;

    /**
     * Sets up a basic router instance for usage in the tests.
     */
    public function setUp()
    {
        $this->router = new Router();
    }

    /**
     * Basic closure for testing the response and execution of routes.
     */
    public function closure()
    {
        return 'Response';
    }

    /**
     * @param Route $route
     * @param string|array $methods
     */
    private function assertBasicRoute($route, $methods)
    {
        if (!is_array($methods)) {
            $methods = array($methods);
        }

        $this->assertEquals('/', $route->uri());
        $this->assertEquals($methods, $route->methods());
        $this->assertEquals('Response', $route->action()());
        $this->assertEquals('Response', $route->execute());
    }

    /**
     * Tests that a GET route is properly generated with all needed properties.
     */
    public function testGenerateGetRoute()
    {
        $route = $this->router->createGetRoute('/', array($this, 'closure'));
        $this->assertBasicRoute($route, 'GET');
    }

    /**
     * Tests that a POST route is properly generated with all needed properties.
     */
    public function testGeneratePostRoute()
    {
        $route = $this->router->createPostRoute('/', array($this, 'closure'));
        $this->assertBasicRoute($route, 'POST');
    }

    /**
     * Tests that a PUT route is properly generated with all needed properties.
     */
    public function testGeneratePutRoute()
    {
        $route = $this->router->createPutRoute('/', array($this, 'closure'));
        $this->assertBasicRoute($route, 'PUT');
    }

    /**
     * Tests that a PATCH route is properly generated with all needed properties.
     */
    public function testGeneratePatchRoute()
    {
        $route = $this->router->createPatchRoute('/', array($this, 'closure'));
        $this->assertBasicRoute($route, 'PATCH');
    }

    /**
     * Tests that an OPTIONS route is properly generated with all needed properties.
     */
    public function testGenerateOptionsRoute()
    {
        $route = $this->router->createOptionsRoute('/', array($this, 'closure'));
        $this->assertBasicRoute($route, 'OPTIONS');
    }

    /**
     * Tests that a DELETE route is properly generated with all needed properties.
     */
    public function testGenerateDeleteRoute()
    {
        $route = $this->router->createDeleteRoute('/', array($this, 'closure'));
        $this->assertBasicRoute($route, 'DELETE');
    }

    /**
     * Tests that a HEAD route is properly generated with all needed properties.
     */
    public function testGenerateHeadRoute()
    {
        $route = $this->router->createHeadRoute('/', array($this, 'closure'));
        $this->assertBasicRoute($route, 'HEAD');
    }

    /**
     * Tests that a match route is properly generated with all needed properties.
     */
    public function testGenerateMatchRoute()
    {
        $route = $this->router->createMatchRoute(array('GET', 'POST', 'PUT'), '/', array($this, 'closure'));
        $this->assertBasicRoute($route, array('GET', 'POST', 'PUT'));
    }

    /**
     * Tests that an any route is properly generated with all needed properties.
     */
    public function testGenerateAnyRoute()
    {
        $route = $this->router->createAnyRoute('/', array($this, 'closure'));
        $this->assertBasicRoute($route, Router::$verbs);
    }

    /**
     * Tests that a view route is properly generated and correctly points to the ViewActionController.
     */
    public function testGenerateViewRoute()
    {
        $route = $this->router->createViewRoute('/', 'test');

        $this->assertEquals(Router::$verbs, $route->methods());
        $this->assertEquals('/', $route->uri());
        $this->assertEquals('Horizon\Routing\Controllers\ViewActionController::__invoke', $route->action());
    }

    /**
     * Tests that a redirect route is properly generated and points to the RedirectActionController.
     */
    public function testGenerateRedirectRoute()
    {
        $route = $this->router->createRedirectRoute('/', '/test', 301);

        $this->assertEquals(Router::$verbs, $route->methods());
        $this->assertEquals('/', $route->uri());
        $this->assertEquals('Horizon\Routing\Controllers\RedirectActionController::__invoke', $route->action());
        $this->assertEquals('/test', $route->getDefault('to'));
        $this->assertEquals(301, $route->getDefault('code'));
    }

    /**
     * Tests the generation of groups and the retrieval of their properties.
     */
    public function testGroup()
    {
        $group = $this->router->createGroup(function() {
            $get = $this->router->createGetRoute('/', array($this, 'closure'));
            $this->assertEquals('/', $get->uri());
        });

        $group2 = $this->router->createGroup(array('prefix' => '/dir'), function() {
            $get = $this->router->createGetRoute('/', array($this, 'closure'));
            $this->assertEquals('/dir/', $get->uri());
        });

        $this->assertEquals('', $group->prefix());
        $this->assertEquals('/dir', $group2->prefix());
    }

    /**
     * Tests the isolated sandbox state of groups to ensure properties don't leak to parent groups.
     */
    public function testGroupIsolation()
    {
        $group = $this->router->createGroup(function() {
            $this->router->createPrefix('/isolated');
            $get = $this->router->createGetRoute('/', array($this, 'closure'));

            $this->assertEquals('/isolated/', $get->uri());
        });

        $get = $this->router->createGetRoute('/', array($this, 'closure'));

        $this->assertEquals('/isolated', $group->prefix());
        $this->assertEquals('/', $get->uri());
    }

    /**
     * Tests the inheritance ability of groups to build upon the properties of parent groups.
     */
    public function testGroupInheritance()
    {
        $group = $this->router->createGroup(function() {
            $this->router->createPrefix('/first');

            $group = $this->router->createGroup(function() {
                $this->router->createPrefix('/second');
                $get = $this->router->createGetRoute('/', array($this, 'closure'));

                $this->assertEquals('/first/second/', $get->uri());
            });
        });
    }

    /**
     * Tests the generation and storage of middleware via groups.
     */
    public function testMiddlewareGroup()
    {
        $group = $this->router->createMiddlewareGroup('TestMiddleware', function() {});
        $this->assertEquals(array('TestMiddleware'), $group->middleware());
    }

    /**
     * Tests the generation and storage of namespaces via groups.
     */
    public function testNamespaceGroup()
    {
        $group = $this->router->createNamespaceGroup('TestNamespace', function() {});
        $this->assertEquals('TestNamespace\\', $group->namespacePrefix());
    }

    /**
     * Tests the generation, storage, and concatenation of names via groups.
     */
    public function testNameGroup()
    {
        $group = $this->router->createNameGroup('test.', function() {
            $route = $this->router->createGetRoute('/', function() {})->name('index');
            $this->assertEquals('test.index', $route->name());
        });

        $this->assertEquals('test.', $group->namePrefix());
    }

    /**
     * Tests domain groups and inheritance of domains in child routes.
     */
    public function testDomainGroup()
    {
        $group = $this->router->createDomainGroup('{subdomain}.example.com', function() {
            $route = $this->router->createGetRoute('/', function() {});

            $this->assertEquals('{subdomain}.example.com', $route->group()->domain());
        });

        $this->assertEquals('{subdomain}.example.com', $group->domain());
    }

    /**
     * Tests to ensure properties of the global default group apply to child groups.
     */
    public function testGlobalInheritance()
    {
        $this->router->createPrefix('/test');
        $this->router->createName('test.');
        $this->router->createNamespace('Test\\');
        $this->router->createDomain('test.com');

        $properties = array(
            'prefix' => '/group',
            'name' => 'group.',
            'namespace' => 'Group\\'
        );

        $group = $this->router->createGroup($properties, function() {
            $route = $this->router->createGetRoute('/', function() { })->name('route');

            $this->assertEquals('test.com', $route->group()->domain());
            $this->assertEquals('test.group.route', $route->name());
            $this->assertEquals('Test\\Group\\', $route->group()->namespacePrefix());
        });
    }

}