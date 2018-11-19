<?php

namespace Horizon\Http\Cookie\Drivers;

use Horizon;
use Horizon\Http\Cookie\Session;
use Horizon\Exception\HorizonException;
use Horizon\Encryption\FastEncrypt;

class CookieDriver implements DriverInterface
{

    private $session;

    private $sessionData = array();
    private $currentFlashData = array();
    private $newFlashData = array();

    private $token = null;

    public function __construct(Session $session)
    {
        $this->session = $session;
        $this->token = $this->getToken();

        $this->startSession();
        $this->load();
        $this->expireFlash();
    }

    /**
     * Gets the unique token for the application. This is to avoid collisions with other variables in the PHP
     * session.
     *
     * @return string
     */
    public function getToken()
    {
        return 'horizon_' . md5(__DIR__);
    }

    /**
     * Starts the session if output has not already been sent to the client.
     */
    private function startSession()
    {
        if (session_status() == PHP_SESSION_NONE) {
            if (!headers_sent()) {
                @session_name(Horizon::config('session.name'));
                session_start();
            }
            else {
                if (Horizon::config('session.throws') && !Horizon::environment('test')) {
                    throw new HorizonException(0x0004, 'CookieDriver init (cannot initialize session: headers already sent)');
                }
            }
        }
    }

    /**
     * Loads the current session data into the driver.
     */
    private function load()
    {
        // Create a default empty payload under our token if needed
        if (!isset($_SESSION[$this->token])) {
            $this->createDefaultSession();
        }
        else {
            $sessionIsEncrypted = $_SESSION[$this->token . '_encrypted'];

            // Handle cases where encryption was disabled
            if ($sessionIsEncrypted == true && !$this->isEncryptionEnabled()) {
                $this->rewriteDisableEncryption();
            }
            elseif ($sessionIsEncrypted == false && $this->isEncryptionEnabled()) {
                $this->rewriteEnableEncryption();
            }
        }

        // Load flash data
        foreach ($_SESSION[$this->token . '_flash'] as $key => $value) {
            $this->currentFlashData[$key] = json_decode($this->decrypt($value), true);
            $this->sessionData[$key] = $this->currentFlashData[$key];
        }

        // Load variables into the payload
        foreach ($_SESSION[$this->token] as $key => $value) {
            $this->sessionData[$key] = json_decode($this->decrypt($value), true);
        }

        // Expire flash data
        $this->expireFlash();
    }

    /**
     * Rewrites the session payload and flash to exclude encryption.
     */
    private function rewriteDisableEncryption()
    {
        // Decrypt flash data
        foreach ($_SESSION[$this->token . '_flash'] as $key => $value) {
            $_SESSION[$this->token . '_flash'][$key] = FastEncrypt::decrypt($value);
        }

        // Decrypt payload
        foreach ($_SESSION[$this->token] as $key => $value) {
            $_SESSION[$this->token][$key] = FastEncrypt::decrypt($value);
        }

        // Disable encryption
        $_SESSION[$this->token . '_encrypted'] = false;
    }

    /**
     * Rewrites the session payload and flash to include encryption.
     */
    private function rewriteEnableEncryption()
    {
        // Encrypt flash data
        foreach ($_SESSION[$this->token . '_flash'] as $key => $value) {
            $_SESSION[$this->token . '_flash'][$key] = FastEncrypt::encrypt($value);
        }

        // Encrypt payload
        foreach ($_SESSION[$this->token] as $key => $value) {
            $_SESSION[$this->token][$key] = FastEncrypt::encrypt($value);
        }

        // Enable encryption
        $_SESSION[$this->token . '_encrypted'] = true;
    }

    /**
     * Creates the default session arrays.
     */
    private function createDefaultSession()
    {
        $_SESSION['horizon_framework_token'] = $this->token;

        $_SESSION[$this->token] = array();
        $_SESSION[$this->token . '_flash'] = array();
        $_SESSION[$this->token . '_encrypted'] = $this->isEncryptionEnabled();
        $_SESSION[$this->token . '_init'] = time();
    }

    /**
     * Deletes expired flash data from the session. This does not impact any of the accessible data from within the
     * current flash.
     */
    private function expireFlash()
    {
        $_SESSION[$this->token . '_flash'] = array();
    }

    /**
     * Checks whether session encryption is currently enabled.
     *
     * @return bool
     */
    public function isEncryptionEnabled()
    {
        return Horizon::config('session.encrypt');
    }

    /**
     * Decrypts the provided string if session.encrypt is enabled in configuration.
     *
     * @param string $str
     * @return string
     */
    private function encrypt($str)
    {
        if ($this->isEncryptionEnabled()) {
            return FastEncrypt::encrypt($str);
        }

        return $str;
    }

    /**
     * Decrypts the provided string if session.encrypt is enabled in configuration.
     *
     * @param string $str
     * @return string
     */
    private function decrypt($str)
    {
        if ($this->isEncryptionEnabled()) {
            return FastEncrypt::decrypt($str);
        }

        return $str;
    }

    /**
     * Determines if the session has the specified key AND it is not null.
     *
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return isset($this->sessionData[$key]);
    }

    /**
     * Determines if the session has the specified key. Returns true even if the value is null.
     *
     * @param string $key
     * @return bool
     */
    public function exists($key)
    {
        return array_key_exists($key, $this->sessionData);
    }

    /**
     * Stores data in the session under the specified key, overwriting any existing values.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return void
     */
    public function put($key, $value)
    {
        $this->sessionData[$key] = $value;
        $_SESSION[$this->token][$key] = $this->encrypt(json_encode($value));
    }

    /**
     * Gets the value of the specified key in the session.  If the key does not exist, returns the specified default
     * value (or null).
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if ($this->exists($key)) {
            return $this->sessionData[$key];
        }

        return $default;
    }

    /**
     * Gets the value of the specified key and then removes it from the session. If the key does not exist, returns
     * the specified default value (or null).
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function pull($key, $default = null)
    {
        if ($this->exists($key)) {
            // Get the value
            $value = $this->get($key);

            // Delete the key from the session
            $this->forget($key);

            // Return the value
            return $value;
        }

        return $default;
    }

    /**
     * Forgets the specified key, effectively removing it from the session.
     *
     * @param string $key
     * @return void
     */
    public function forget($key)
    {
        if ($this->exists($key)) {
            unset($this->sessionData[$key]);

            // Remove from session only if it is not flash data
            if (array_key_exists($key, $_SESSION[$this->token])) {
                unset($_SESSION[$this->token][$key]);
            }
        }
    }

    /**
     * Flashes data to the session which will only persist until the next session activation (typically the next
     * pageload).
     *
     * @param string $key
     * @param mixed $value
     */
    public function flash($key, $value)
    {
        $this->newFlashData[$key] = $value;
        $_SESSION[$this->token . '_flash'][$key] = $this->encrypt(json_encode($value));
    }

    /**
     * Reflashes the current flashed data, effectively persisting it until the next pageload. Note that if any keys in
     * the reflashed data have been written to the current flash, they will be overwritten with the old data.
     */
    public function reflash()
    {
        foreach ($this->currentFlashData as $key => $value) {
            $this->flash($key, $value);
        }
    }

    /**
     * Reflashes the specified keys only. Does not error if the specified keys do not exist.
     * @see reflash()
     *
     * @param string[] $keys
     */
    public function keep(array $keys)
    {
        foreach ($this->currentFlashData as $key => $value) {
            if (in_array($key, $keys)) {
                $this->flash($key, $value);
            }
        }
    }

    /**
     * Gets an array of all payload keys.
     *
     * @return array
     */
    public function all()
    {
        return $this->sessionData;
    }

    /**
     * Gets the current CSRF token.
     *
     * @return string
     */
    public function csrf()
    {
        if (!isset($_SESSION[$this->token . '_sectoken'])) {
            $this->renew();
        }

        return $_SESSION[$this->token . '_sectoken'];
    }

    /**
     * Releases the current CSRF token and generates a new one.
     *
     * @return string
     */
    public function renew()
    {
        $_SESSION[$this->token . '_sectoken'] = bin2hex(\phpseclib\Crypt\Random::string(16));
    }

}