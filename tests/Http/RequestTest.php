<?php

use PHPUnit\Framework\TestCase;
use Horizon\Events\EventEmitter;
use Horizon\Http\Request;

class RequestTest extends TestCase
{

    /**
     * Set up a basic POST request for testing.
     */
    public function setUp()
    {
        $this->request = new Request(
            array('id' => '50', 'name' => 'John Doe'), // query
            array('password' => 'super secure', 'name' => 'Jane Doe'), // request
            array(), // attributes
            array('cookie1' => 'a', 'cookie2' => 'b'), // cookies
            array('file1' => array('error' => UPLOAD_ERR_INI_SIZE)), // files
            array( // headers
                'CONTENT_TYPE' => 'text/html',
                'REQUEST_URI' => '/some/path?id=50&name=John%20Doe',
                'REQUEST_METHOD' => 'POST',
                'HTTP_HOST' => 'example.com',
                'HTTPS' => true,
                'HTTP_USER_AGENT' => 'phpunit',
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
                'REMOTE_ADDR' => '12.34.56.78',
                'HTTP_LIST' => 'a; b; c;'
            ),
            '{"working": "true", "nested":{"working": "true"}}' // content
        );
    }

    /**
     * Checks proper formatting and detection of request path.
     */
    public function testPath()
    {
        $this->assertEquals('/some/path', $this->request->path());
    }

    /**
     * Checks proper formatting and detection of current URL, without query args.
     */
    public function testUrl()
    {
        $this->assertEquals('https://example.com/some/path', $this->request->url());
    }

    /**
     * Checks proper formatting and generation of URL with a specified path.
     */
    public function testUrlWithPath()
    {
        $this->assertEquals('https://example.com/test', $this->request->url('test'));
        $this->assertEquals('https://example.com/test', $this->request->url('/test'));
    }

    /**
     * Checks proper formatting and detection of current URL with queries.
     */
    public function testFullUrl()
    {
        $this->assertEquals('https://example.com/some/path?id=50&name=John%20Doe', $this->request->fullUrl());
    }

    /**
     * Checks proper formatting and generation of specific path with queries.
     */
    public function testFullUrlWithPath()
    {
        $this->assertEquals('https://example.com/test?test=1', $this->request->fullUrl('test?test=1'));
        $this->assertEquals('https://example.com/test?test=1', $this->request->fullUrl('/test?test=1'));
    }

    /**
     * Checks proper formatting of the root URL.
     */
    public function testRoot()
    {
        $this->assertEquals('https://example.com/', $this->request->root());
    }

    /**
     * Checks proper detection of XMLHttpRequest headers.
     */
    public function testAjax()
    {
        $this->assertTrue($this->request->ajax());
    }

    /**
     * Checks proper detection of SSL (https).
     */
    public function testSecure()
    {
        $this->assertTrue($this->request->secure());
    }

    /**
     * Checks proper detection of client IP address (test assumes IPv4).
     */
    public function testIpAddress()
    {
        $this->assertEquals('12.34.56.78', $this->request->ip());
    }

    /**
     * Checks proper detection of user agent header.
     */
    public function testUserAgent()
    {
        $this->assertEquals('phpunit', $this->request->userAgent());
    }

    /**
     * Checks the integrity of header values retrieved from the request.
     */
    public function testHeaders()
    {
        $this->assertEquals('text/html', $this->request->header('content-type'));
        $this->assertEquals('text/html', $this->request->header('content_type'));
        $this->assertEquals('a; b; c;', $this->request->header('list'));
    }

    /**
     * Tests the ability to get query information.
     */
    public function testGetQuery()
    {
        $this->assertEquals('50', $this->request->query('id'));
        $this->assertEquals(0x1, $this->request->query('nonexistent', 0x1));
        $this->assertNull($this->request->query('password'));
    }

    /**
     * Tests the ability to get posted information.
     */
    public function testGetPost()
    {
        $this->assertEquals('super secure', $this->request->post('password'));
        $this->assertEquals('nonexistent', $this->request->post('some_key', 'nonexistent'));
        $this->assertNull($this->request->post('id'));
    }

    /**
     * Tests the ability to get unified input.
     */
    public function testGetInput()
    {
        // Retrieve independent GET/POST variables
        $this->assertEquals('super secure', $this->request->input('password'));
        $this->assertEquals('50', $this->request->input('id'));

        // Retrieve overridden variables (POST has priority over GET)
        $this->assertEquals('Jane Doe', $this->request->input('name'));

        // Defaults
        $this->assertTrue($this->request->input('nonexistent', true));
    }

    /**
     * Tests the ability to retrieve uploaded files as arrays.
     */
    public function testGetFiles()
    {
        $this->assertEquals(1, count($this->request->files()));
        $this->assertEquals(UPLOAD_ERR_INI_SIZE, $this->request->files()['file1']['error']);
    }

    /**
     * Tests shorthand file property access.
     */
    public function testGetFile()
    {
        $this->assertEquals(UPLOAD_ERR_INI_SIZE, $this->request->file('file1', 'error'));
        $this->assertEquals(UPLOAD_ERR_INI_SIZE, $this->request->file('file1')['error']);
    }

    /**
     * Tests JSON content decoding and error handling.
     */
    public function testJson()
    {
        $json = $this->request->json();

        $this->assertEquals('true', $json['working']);
        $this->assertNull($this->request->jsonError());
    }

}
