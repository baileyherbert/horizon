<?php

namespace Horizon\Support\Web;

use Horizon\Foundation\Framework;

/**
 * This is a utility class for sending HTTP requests. It uses the cURL library internally and offers many advantages
 * such as easier cookie management, response traces, and easier exceptions.
 */
class WebRequest {

    const HEADER_STATUS = 0x1;
    const HEADER_LINE = 0x2;

    /**
     * The URL to target for this web request.
     *
     * @var string
     */
    protected $uri;

    /**
     * Options to use with the CURL request.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Headers to send with the CURL request.
     *
     * @var string[]
     */
    protected $headers = [];

    /**
     * Cookies to send with the CURL request.
     *
     * @var string[]
     */
    protected $cookies = [];

    /**
     * Link to send to the server as our referer.
     *
     * @var string|null
     */
    protected $referer = null;

    /**
     * The user agent to use with this web request.
     *
     * @var string
     */
    protected $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36';

    /**
     * A custom header function provided by the caller, if available.
     *
     * @var mixed
     */
    protected $customHeaderFunction;

    /**
     * Internal body buffer for abstractions that use a WRITEFUNCTION.
     *
     * @var string|null
     */
    protected $bodyBuffer;

    /**
     * Constructs a new `WebRequest` instance for the given URL.
     *
     * @param string $uri
     */
    public function __construct($uri) {
        $this->uri = $uri;

        $this->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->setOption(CURLOPT_FOLLOWLOCATION, true);
        $this->setOption(CURLOPT_MAXREDIRS, 5);
        $this->setOption(CURLOPT_ENCODING, '');
        $this->setOption(CURLOPT_CAINFO, Framework::path('resources/ca-bundle.crt'));

        // Determine the current curl version
        $curl_version = curl_version();

        // Prefer HTTP/2 where available (will fall back to HTTP/1.1 if not)
        if (version_compare($curl_version['version'], '7.33.0', '>=')) {
            if (defined('CURL_VERSION_HTTP2') && ($curl_version['features'] & constant('CURL_VERSION_HTTP2')) !== 0) {
                $this->setOption(CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
            }
        }
    }

    /**
     * Returns the current version of the WebRequest library.
     */
    public static function version() {
        return '1.0.0';
    }

    /**
     * Sets a CURL option on the request.
     *
     * @param int $option
     * @param mixed $value
     */
    public function setOption($option, $value) {
        if ($option === CURLOPT_HTTPHEADER) {
            if (is_array($value)) {
                foreach ($value as $header => $headerValue) {
                    $this->setHeader($header, $headerValue);
                }
            }

            return;
        }

        if ($option === CURLOPT_USERAGENT) {
            return $this->setUserAgent($value);
        }

        if ($option === CURLOPT_REFERER) {
            return $this->setReferer($value);
        }

        $this->options[$option] = $value;
    }

    /**
     * Sets multiple options on the request at once using an array.
     *
     * @param array $options
     */
    public function setOptions($options) {
        foreach ($options as $option => $value) {
            $this->setOption($option, $value);
        }
    }

    /**
     * Returns the current value of the specified CURL option if set. Otherwise, returns `null`.
     *
     * @param string $option
     * @return mixed
     */
    public function getOption($option) {
        if (array_key_exists($option, $this->options)) {
            return $this->options[$option];
        }

        return null;
    }

    /**
     * Returns an array of all options configured on the request.
     *
     * @return array
     */
    public function getOptionsArray() {
        return $this->options;
    }

    /**
     * Sets the user agent to use for this web request.
     *
     * @param string $agent
     */
    public function setUserAgent($agent) {
        $this->userAgent = $agent;
    }

    /**
     * Returns the web request's current user agent string.
     *
     * @return string
     */
    public function getUserAgent() {
        return $this->userAgent;
    }

    /**
     * Sets the value of a header to send with the request. If the given value is `null`, the header will be removed
     * from the request.
     *
     * @param string $header
     * @param string|null $value
     */
    public function setHeader($header, $value) {
        $header = strtolower($header);

        if ($header === 'referer') {
            return $this->setReferer($value);
        }

        if (is_null($value)) {
            if (array_key_exists($header, $this->headers)) {
                unset($this->headers[$header]);
            }

            return;
        }

        $this->headers[$header] = $value;
    }

    /**
     * Returns an array of headers which will be sent with this request.
     *
     * @return string[]
     */
    public function getHeaders() {
        return $this->headers;
    }

    /**
     * Sets the referer to use for this web request.
     *
     * @param string $referer
     */
    public function setReferer($referer) {
        $this->referer = $referer;
    }

    /**
     * Returns the referer that will be used for this web request, or `null` if one is not set.
     *
     * @return string|null
     */
    public function getReferer() {
        return $this->referer;
    }

    /**
     * Sets the value of a cookie to send with the request. If the given value is `null`, the cookie will be removed
     * from the request.
     *
     * @param string $name
     * @param string|null $value
     */
    public function setCookie($name, $value) {
        if (is_null($value)) {
            if (isset($this->cookies[$name])) {
                unset($this->cookies[$name]);
            }

            return;
        }

        $this->cookies[$name] = $value;
    }

    /**
     * Sets multiple cookies simultaneously via an array, where the keys are the cookie names. Alternatively, you may
     * pass an array of `WebResponseCookie` instances.
     *
     * @param array $cookies
     */
    public function setCookies($cookies) {
        foreach ($cookies as $name => $value) {
            if (is_object($value) && $value instanceof WebResponseCookie) {
                if ($value->getExpirationTime() > time()) {
                    $name = $value->getName();
                    $value = $value->getValue();
                }
                else {
                    continue;
                }
            }

            $this->setCookie($name, $value);
        }
    }

    /**
     * Returns an array of all cookies that will be sent with this request.
     *
     * @return string[]
     */
    public function getCookies() {
        return $this->cookies;
    }

    /**
     * Sets the highest number of seconds the request can take before it is interrupted.
     *
     * @param int $timeout
     */
    public function setTimeout($timeout) {
        $this->setOption(CURLOPT_TIMEOUT, $timeout);
    }

    /**
     * Sets the maximum number of redirects (shortcut for the `CURLOPT_MAXREDIRS` option).
     *
     * @param int $redirects
     */
    public function setMaxRedirects($redirects) {
        $this->setOption(CURLOPT_MAXREDIRS, $redirects);
    }

    /**
     * Sets whether or not to follow redirects.
     *
     * @param bool $follow
     * @param int $redirects The maximum number of redirects to follow (optional).
     */
    public function setFollowRedirects($follow, $redirects = null) {
        $this->setOption(CURLOPT_FOLLOWLOCATION, $follow);

        if (!is_null($redirects)) {
            $this->setMaxRedirects($redirects);
        }
    }

    /**
     * Executes this request with the `GET`  request method and returns the response.
     *
     * @return WebResponse
     * @throws WebRequestException Thrown for network, connection, or configuration errors.
     */
    public function get() {
        return $this->execute('get');
    }

    /**
     * Executes this request with the `GET`  request method and returns the response.
     *
     * @param string $format The type of formatting to use for posting (`json` or `urlencoded`).
     *
     * @return WebResponse
     * @throws WebRequestException Thrown for network, connection, or configuration errors.
     */
    public function post($fields = [], $format = 'urlencoded') {
        $format = strtolower($format);
        $headers = [];

        // If the format is 'json', convert arrays to a JSON string
        if ($format === 'json') {
            if (is_array($fields)) $fields = json_encode($fields, JSON_UNESCAPED_SLASHES);
            $headers['content-type'] = 'application/json';
        }

        // If the format is 'urlencoded', convert arrays to a query string
        elseif ($format === 'urlencoded') {
            if (is_array($fields)) $fields = http_build_query($fields);
            $headers['content-type'] = 'application/x-www-form-urlencoded';
        }

        // If the format is anything else, set the content type directly
        else {
            $headers['content-type'] = $format;
        }

        // Perform the request
        return $this->execute('post', [ CURLOPT_POSTFIELDS => $fields ], $headers);
    }

    /**
     * Executes this request with the specified request method and the given options.
     *
     * @param string $method
     * @param array $options
     *
     * @return WebResponse
     * @throws WebRequestException Thrown for network, connection, or configuration errors.
     */
    public function execute($method = 'get', $options = [], $headers = []) {
        // Time profiling
        $startTime = microtime(true);

        // Get request headers
        $headers = $this->calculateHeaders($headers);
        $responseHeaders = [];
        $httpVersion = null;
        $httpMessage = null;

        // Get options and set the header parser
        $options = $this->calculateOptions($options, $method, $this->createHeaderParser($responseHeaders, $httpVersion, $httpMessage));

        // Determine if we should follow redirects
        $isFollowing = $this->isFollowingRedirects();
        $uri = $lastLocation = $this->uri;
        $redirections = 0;
        $traces = [];

        // Perform requests in a loop until we're done
        while (true) {
            $this->bodyBuffer = null;

            $handle = $this->createHandle($uri, $options, $headers);
            $body = @curl_exec($handle);
            $traces[] = new WebResponseTrace($uri, $handle);

            // Handle client errors
            if (curl_errno($handle) > 0) {
                throw new WebRequestException($this, curl_strerror(curl_errno($handle)), curl_errno($handle));
            }

            // Follow redirections if configured to do so
            if ($isFollowing) {
                $location = isset($responseHeaders['location']) ? $responseHeaders['location'] : null;

                // Fix for a rare bug -- if multiple locations are provided, choose the last
                if (is_array($location)) {
                    $location = $location[count($location) - 1];
                }

                // If $redirect is not null, then the page wants to redirect
                if (!is_null($location)) {
                    // Make sure we're under the redirect limit
                    if (($redirections++) >= $this->getOption(CURLOPT_MAXREDIRS)) {
                        throw new WebRequestException($this, 'Too many redirects', CURLE_TOO_MANY_REDIRECTS);
                    }

                    // Parse and update the uri
                    $previousUri = $uri;
                    $uri = $this->parseRedirectUri($lastLocation, $location);

                    // Clean up for next iteration
                    $responseHeaders = [];
                    $httpVersion = null;
                    $lastLocation = $previousUri;

                    // Continue to the next iteration
                    continue;
                }
            }

            // Calculate total time
            $totalTime = (microtime(true) - $startTime);

            // Return internal body
            if ($body === true && isset($this->bodyBuffer)) {
                $body = $this->bodyBuffer;
                unset($this->bodyBuffer);
            }

            // Return the response instance
            return new WebResponse($this, $handle, $responseHeaders, $body, $totalTime, $traces, $httpVersion, $httpMessage);
        }
    }

    /**
     * Returns a new CURL resource configured for the given parameters.
     *
     * @param string $uri
     * @param array $options
     * @param array $headers
     */
    protected function createHandle($uri, $options, $headers) {
        if (!function_exists('curl_init')) {
            throw new \Exception('cURL is not available on this server');
        }

        $ch = curl_init($uri);

        if ($ch === false) {
            throw new \Exception('Failed to initialize cURL');
        }

        // Set the options
        curl_setopt_array($ch, $options);

        // Set the headers
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Return the handle resource
        return $ch;
    }

    /**
     * Returns an array of all headers to send for the request.
     *
     * @param string[] $userHeaders
     * @return array
     */
    protected function calculateHeaders($userHeaders = []) {
        $original = $this->getHeaders();
        $headers = [];
        $skipCookies = false;

        // Add cookies
        if (!empty($this->cookies)) {
            $skipCookies = true;
            $headers[] = 'Cookie: ' . $this->calculateCookies();
        }

        // Add client headers
        foreach ($original as $header => $value) {
            $header = strtolower($header);
            if ($skipCookies && $header === 'cookie') continue;

            $headers[] = $header . ': ' . $value;
        }

        // Add user headers
        foreach ($userHeaders as $header => $value) {
            $header = strtolower($header);
            if ($skipCookies && $header === 'cookie') continue;

            $headers[] = $header . ': ' . $value;
        }

        // Add user headers and return
        return $headers;
    }

    /**
     * Returns an array of all options to use for the request.
     *
     * @param array $userOptions
     * @param string $method
     * @param callback $headerFunction
     * @return array
     */
    protected function calculateOptions($userOptions, $method, $headerFunction) {
        $options = $this->getOptionsArray();

        // Add the request method
        $options[CURLOPT_CUSTOMREQUEST] = strtoupper($method);

        // Add user options
        $options = array_replace($options, $userOptions);

        // Set the followlocation option to false, we'll do this manually to prevent errors
        $options[CURLOPT_FOLLOWLOCATION] = false;

        // Record a custom header function
        if (isset($options[CURLOPT_HEADERFUNCTION])) {
            $this->customHeaderFunction = $options[CURLOPT_HEADERFUNCTION];
        }

        // Get response headers
        $options[CURLOPT_HEADERFUNCTION] = $headerFunction;

        // Referer
        if (!is_null($this->referer)) $options[CURLOPT_REFERER] = $this->referer;

        // User agent
        if (!is_null($this->userAgent)) $options[CURLOPT_USERAGENT] = $this->getUserAgent();

        return $options;
    }

    /**
     * Returns an encoded string containing all cookies in the request in proper header format.
     *
     * @return string
     */
    protected function calculateCookies() {
        $string = '';

        foreach ($this->cookies as $name => $value) {
            $string .= sprintf('%s=%s; ', $name, $value);
        }

        return trim($string);
    }

    /**
     * Returns `true` if this request is configured to follow redirections.
     *
     * @return bool
     */
    protected function isFollowingRedirects() {
        return isset($this->options[CURLOPT_FOLLOWLOCATION]) && $this->options[CURLOPT_FOLLOWLOCATION];
    }

    /**
     * Returns a callback which parses response headers and populates them into the given `$headers` array.
     *
     * @param array $headers
     * @param string[] $httpVersion
     * @return callback
     */
    protected function createHeaderParser(&$headers, &$httpVersion, &$httpMessage) {
        return (function($handle, $header) use (&$headers, &$httpVersion, &$httpMessage) {
            $length = strlen($header);
            $header = explode(':', $header, 2);

            if (count($header) < 2) {
                if (preg_match('/^HTTP\/([\d\.]+)\s*(.*)$/i', $header[0], $matches)) {
                    $httpVersion = $matches[1];
                    $httpMessage = trim($matches[2]);

                    if ($this->customHeaderFunction) {
                        $callable = $this->customHeaderFunction;
                        $callable(self::HEADER_STATUS, (int) preg_replace('/[^0-9]/', '', $httpMessage), null);
                    }
                }

                return $length;
            }

            $headerName = strtolower(trim($header[0]));
            $headerValue = trim($header[1]);

            if (!isset($headers[$headerName])) {
                $headers[$headerName] = $headerValue;
            }
            else {
                if (!is_array($headers[$headerName])) {
                    $headers[$headerName] = [$headers[$headerName]];
                }

                $headers[$headerName][] = $headerValue;
            }

            if ($this->customHeaderFunction) {
                $callable = $this->customHeaderFunction;
                $callable(self::HEADER_LINE, $headerName, $headerValue);
            }

            return $length;
        });
    }

    /**
     * Parses the given location uri into an absolute url.
     *
     * @param string $handle
     * @return string
     */
    protected function parseRedirectUri($lastUri, $uri) {
        $base = parse_url($lastUri);

        if (substr($uri, 0, 2) === '//') return ($base['scheme'] . '://' . substr($uri, 2));
        if (substr($uri, 0, 1) === '/') return ($base['scheme'] . '://' . $base['host'] . $uri);
        if (substr($uri, 0, 2) === './') return ($base['scheme'] . '://' . $base['host'] . '/' . substr($uri, 2));
        if (strpos($uri, '://') === false) return ($base['scheme'] . '://' . $base['host'] . $uri);

        return $uri;
    }

    /**
     * Returns a new `WebRequest` instance with the same settings as the current instance.
     *
     * @return WebRequest
     */
    public function copy() {
        $request = new WebRequest($this->uri);

        $request->setOptions($this->options);
        $request->setUserAgent($this->userAgent);
        $request->setReferer($this->referer);

        return $request;
    }

}
