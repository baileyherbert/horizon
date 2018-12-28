<?php

namespace Horizon\Routing\Controllers;

use Horizon\Foundation\Framework;
use Horizon\Http\Request;
use Horizon\Http\Response;
use Horizon\Http\Controller;
use Horizon\Support\Str;
use GuzzleHttp\Client;
use Horizon\Support\Path;
use Horizon\Http\Exception\HttpResponseException;

class TcpProxyController extends Controller
{

    private $address;
    private $port;
    private $timeout;

    private $caBundlePath;

    private $method;
    private $url;
    private $post;
    private $headers;

    private $ssl;

    public function __invoke(Request $request, Response $response, $_address, $_port, $_timeout, $_caBundle)
    {
        $this->address = $_address;
        $this->port = $_port;
        $this->timeout = $_timeout;

        $this->caBundlePath = $_caBundle;

        $this->method = $request->getMethod();
        $this->url = $this->getRealAddress();
        $this->post = $this->getPostArgs();
        $this->headers = $this->getHeaders();

        $this->ssl = $this->isSSL();
        $this->performHandlingErrors();
    }

    /**
     * Gets the real URL to connect to.
     *
     * @return string
     */
    private function getRealAddress()
    {
        $input = $this->address;

        if (!preg_match('/^https?:\/\//i', $input)) {
            $s = ($this->port == 443) ? 's' : '';
            $input = 'http' . $s . '://' . $input;
        }

        $parse = parse_url($input);

        if (!isset($parse['path'])) $parse['path'] = '/';
        if (!isset($parse['query'])) $parse['query'] = '';

        $url = sprintf(
            '%s://%s:%d%s%s',
            strtolower($parse['scheme']),
            strtolower($parse['host']),
            $this->port,
            $parse['path'],
            $this->getQueryArgs($parse['query'])
        );

        return $url;
    }

    /**
     * Builds the query string for the connection URL, filling both query parameters that were already in the URL, and
     * parameters from the current Request instance. Returned string is either blank or starts with '?'.
     *
     * @param string $original
     * @return string
     */
    private function getQueryArgs($original)
    {
        $args = array();

        // Parse original
        if (!empty($original)) {
            parse_str($original, $parsed);

            foreach ($parsed as $key => $value) {
                $args[$key] = $value;
            }
        }

        // Add queries from the request
        foreach ($_GET as $key => $value) {
            if ($key == '_address' && $value == $this->address) continue;
            if ($key == '_port' && $value == $this->port) continue;
            if ($key == '_timeout' && $value == $this->timeout) continue;
            if ($key == '_caBundle' && $value == $this->caBundlePath) continue;

            $args[$key] = $value;
        }

        // Build the new string and return it
        $str = http_build_query($args);
        if (!empty($str)) $str = '?' . $str;

        return $str;
    }

    /**
     * Gets the POST data, supporting standard query format as well as JSON and other types of data.
     *
     * @return string|array
     */
    private function getPostArgs()
    {
        if ($this->method == 'GET') {
            return array();
        }

        if (!empty($_POST)) {
            return $_POST;
        }

        return $this->getRequest()->getContent();
    }

    /**
     * Gets the headers to use for the request.
     *
     * @return array
     */
    private function getHeaders()
    {
        $remove = array('host', 'connection', 'cache-control', 'user-agent', 'x-forwarded-for', 'upgrade-insecure-requests', 'set-cookie', 'cookie');
        $headers = $this->getRequest()->header();

        // Remove extraneous headers
        foreach ($remove as $name) {
            $found = null;

            foreach ($headers as $i => $v) {
                if (strcasecmp(trim($i), $name) === 0) {
                    $found = $i;
                }
            }

            if (!is_null($found)) {
                unset($headers[$found]);
            }
        }

        // Convert arrays to strings
        foreach ($headers as $i => $v) {
            if (is_array($v)) {
                if (count($v) === 0) {
                    $headers[$i] = '';
                }
                elseif (count($v) === 1) {
                    $headers[$i] = $v[0];
                }
            }
        }

        // Set custom headers
        $headers['x-forwarded-for'] = $_SERVER['REMOTE_ADDR'];
        $headers['user-agent'] = 'Horizon ' . Framework::version() . ' (Proxy)';

        return $headers;
    }

    /**
     * Checks whether the current request is using SSL.
     *
     * @return bool
     */
    private function isSSL()
    {
        return Str::startsWith($this->url, 'https://') && $this->caBundlePath !== false;
    }

    /**
     * Gets the absolute path to a certificate authority bundle file.
     *
     * @return string
     */
    private function getCertificatePath()
    {
        $default = Path::join(Framework::path('horizon'), 'resources/ca-bundle.crt');

        if ($this->caBundlePath === false) return false;
        if (is_null($this->caBundlePath)) return $default;

        $path = Path::join(Framework::path(), $this->caBundlePath);
        if (!file_exists($path)) return $default;

        return $path;
    }

    /**
     * Performs the request.
     *
     * @return void
     */
    private function perform()
    {
        $client = new Client();
        $method = $this->method;

        $res = $client->get($this->url, array(
            'headers' => $this->headers,
            'body' => $this->post,
            'timeout' => $this->timeout,
            'connect_timeout' => $this->timeout,
            'verify' => $this->getCertificatePath()
        ));

        $this->getResponse()->setStatusCode($res->getStatusCode());
        $this->getResponse()->write($res->getBody());

        $skip = array(
            'content-encoding', 'date', 'keep-alive', 'p3p', 'server', 'set-cookie',
            'transfer-encoding', 'x-frame-options', 'x-xss-protection', 'alt-svc'
        );

        foreach ($res->getHeaders() as $name => $value) {
            $skipHeader = false;

            foreach ($skip as $item) {
                if (strcasecmp($name, $item) === 0) {
                    $skipHeader = true;
                }
            }

            if (!$skipHeader) {
                $this->getResponse()->setHeader($name, $value);
            }
        }

        $this->getResponse()->setHeader('X-Horizon-Proxy', 'Forwarded (' . $res->getStatusCode() . ')');
        $this->getResponse()->setHeader('X-Horizon-Status', 'OK');
    }

    private function performHandlingErrors()
    {
        $fail = function($code, $fwd = true, $message = 'OK') {
            $this->getResponse()->setHeader('X-Horizon-Proxy', ($fwd ? 'Forwarded' : 'Not forwarded') . ' (' . $code . ')');
            $this->getResponse()->setHeader('X-Horizon-Status', $message);
            throw new HttpResponseException($code);
        };

        try {
            return $this->perform();
        }
        catch (\GuzzleHttp\Exception\ConnectException $e) {
            $fail(504, false, 'Connect error');
        }
        catch (\GuzzleHttp\Exception\ClientException $e) {
            $fail($e->getResponse()->getStatusCode());
        }
        catch (\GuzzleHttp\Exception\ServerException $e) {
            $fail($e->getResponse()->getStatusCode());
        }
        catch (\GuzzleHttp\Exception\RequestException $e) {
            $fail(500, false, 'Request error');
        }
    }

}
