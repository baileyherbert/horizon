<?php

namespace Horizon\Http\Cookie\Drivers;

use Exception;
use Horizon\Database\Exception\DatabaseException;
use Horizon\Encryption\FastEncrypt;
use Horizon\Exception\HorizonException;
use Horizon\Http\Cookie\CookieInitializationException;
use Horizon\Http\Cookie\Drivers\Database\SessionCookieModel;
use Horizon\Http\Cookie\Session;
use Horizon\Support\Profiler;

class DatabaseDriver implements DriverInterface {

	/**
	 * @var SessionCookieModel|null
	 */
	private $model = null;

	private $sessionData = array();
	private $sessionDataRemote = array();

	private $currentFlashData = array();

	private $newFlashData = array();
	private $newFlashDataRemote = array();

	private $csrfToken = null;

    public function __construct(Session $session) {
		$this->session = $session;
		$this->startSession();
	}

	/**
	 * Starts the session.
	 *
	 * @return void
	 */
	private function startSession() {
		if (request()->cookies->has(config('session.name'))) {
			$sessionId = request()->cookies->get(config('session.name'));
			$this->model = SessionCookieModel::where('id', '=', $sessionId)->forUpdate()->first();

			if ($this->model !== null) {
				$expiresAt = $this->model->expires_at->getTimestamp();
				$expiresIn = $expiresAt - time();

				if ($expiresIn <= 0) {
					$this->model->delete();
					$this->model = null;
				}
				else {
					$this->loadSessionData();
					$duration = config('session.lifetime', 3600);

					if ($expiresIn <= $duration - ($duration * 0.05)) {
						$this->model->expires_at = time() + $duration;
						$this->model->save();
					}
				}
			}
		}

		if ($this->model === null) {
			Profiler::record('Create database session');
			$this->createNewSession();
		}
	}

	/**
	 * Loads data from the model into the driver.
	 *
	 * @return void
	 */
	private function loadSessionData() {
		$data = $this->unserialize($this->model->data);
		$sessionIsEncrypted = $data[0];

		$this->sessionDataRemote = $data[1];
		$this->newFlashDataRemote = $data[2];
		$this->csrfToken = $data[3];

		// Handle cases where encryption was disabled
		if ($sessionIsEncrypted == true && !$this->isEncryptionEnabled()) {
			$this->rewriteDisableEncryption();
		}

		// Handle cases where encryption was enabled
		elseif ($sessionIsEncrypted == false && $this->isEncryptionEnabled()) {
			$this->rewriteEnableEncryption();
		}

		try {
			// Load flash data
			foreach ($data[2] as $key => $value) {
				$this->currentFlashData[$key] = $this->unserialize($this->decrypt($value));
				$this->sessionData[$key] = $this->currentFlashData[$key];
			}

			// Load variables into the payload
			foreach ($data[1] as $key => $value) {
				$this->sessionData[$key] = $this->unserialize($this->decrypt($value));
			}
		}
		catch (CookieInitializationException $ex) {
			// When we fail to parse data, clear the entire session
			$this->clear();
		}

		// Expire flash data
		$this->expireFlash();
	}

	/**
	 * Initializes a new session.
	 *
	 * @return void
	 */
	private function createNewSession() {
		// Generate a unique session identifier
		while (true) {
			$sessionId = $this->generateSessionId();
			if (!SessionCookieModel::find($sessionId)) break;
		}

		// Start the session in the database
		$instance = $this->model = new SessionCookieModel();
		$instance->id = $sessionId;
		$instance->expires_at = time() + config('session.lifetime', 3600);
		$this->commit();

		// Set the cookie
		if (!setcookie(config('session.name'), $sessionId, 0, '/')) {
			throw new HorizonException(0x0004, 'Failed to create session cookie');
		}
	}

	/**
	 * Generates a cryptographically secure session identifier.
	 *
	 * @return string
	 * @throws Exception
	 */
	private function generateSessionId() {
		$alphabet = config('session.sid_alphabet', 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789/-.');

		$alphabetLength = strlen($alphabet);
		$sessionNameLength = config('session.sid_length', 128);
		$sessionName = '';

		for ($i = 0; $i < $sessionNameLength; $i++) {
			$sessionName .= $alphabet[random_int(0, $alphabetLength - 1)];
		}

		return $sessionName;
	}


	/**
	 * Determines if the session has the specified key AND it is not null.
	 *
	 * @param string $key
	 * @return bool
	 */
	public function has($key) {
		return isset($this->sessionData[$key]);
	}

	/**
	 * Determines if the session has the specified key. Returns true even if the value is null.
	 *
	 * @param string $key
	 * @return bool
	 */
	public function exists($key) {
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
	public function put($key, $value) {
		$this->sessionData[$key] = $value;
		$this->sessionDataRemote[$key] = $this->encrypt($this->serialize($value));
		$this->commit();
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
	public function get($key, $default = null) {
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
	public function pull($key, $default = null) {
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
	public function forget($key) {
		if ($this->exists($key)) {
			unset($this->sessionData[$key]);

			// Remove from session only if it is not flash data
			if (array_key_exists($key, $this->sessionDataRemote)) {
				unset($this->sessionDataRemote[$key]);
				$this->commit();
			}
		}
	}

	/**
	 * Clears all data from the session.
	 *
	 * @return void
	 */
	public function clear() {
		$this->sessionData = array();
		$this->sessionDataRemote = array();
		$this->currentFlashData = array();
		$this->newFlashData = array();
		$this->newFlashDataRemote = array();
		$this->commit();
	}

	/**
	 * Flashes data to the session which will only persist until the next session activation (typically the next
	 * pageload).
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function flash($key, $value) {
		$this->newFlashData[$key] = $value;
		$this->newFlashDataRemote[$key] = $this->encrypt($this->serialize($value));
		$this->commit();
	}

	/**
	 * Reflashes the current flashed data, effectively persisting it until the next pageload. Note that if any keys in
	 * the reflashed data have been written to the current flash, they will be overwritten with the old data.
	 */
	public function reflash() {
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
	public function keep(array $keys) {
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
	public function all() {
		return $this->sessionData;
	}

	/**
	 * Gets the current CSRF token.
	 *
	 * @return string
	 */
	public function csrf() {
		if (!isset($this->csrfToken)) {
			$this->renew();
		}

		return $this->csrfToken;
	}

	/**
	 * Releases the current CSRF token and generates a new one.
	 *
	 * @return string
	 */
	public function renew() {
		$this->csrfToken = bin2hex(\phpseclib\Crypt\Random::string(16));
		$this->commit();
	}

	/**
	 * Returns information about a key.
	 *
	 * @param string $key
	 * @return array
	 */
	public function stat($key) {
		if (array_key_exists($key, $this->sessionDataRemote)) {
			$x = $this->sessionDataRemote[$key];
			return [
				'type' => 'persistent',
				'size' => strlen($this->sessionDataRemote[$key])
			];
		}

		if (array_key_exists($key, $this->currentFlashData)) {
			return [
				'type' => 'flash',
				'size' => $this->sizeof($this->currentFlashData[$key])
			];
		}

		if (array_key_exists($key, $this->sessionData)) {
			return [
				'type' => 'temporary',
				'size' => $this->sizeof($this->sessionData[$key])
			];
		}

		return [
			'type' => 'unknown',
			'size' => 0
		];
	}

	private function sizeof($value) {
		return strlen($this->encrypt($this->serialize($value)));
	}

	/**
	 * Checks whether session encryption is currently enabled.
	 *
	 * @return bool
	 */
	public function isEncryptionEnabled() {
		return config('session.encrypt');
	}

	/**
	 * Decrypts the provided string if session.encrypt is enabled in configuration.
	 *
	 * @param string $str
	 * @return string
	 */
	private function encrypt($str) {
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
	private function decrypt($str) {
		if ($this->isEncryptionEnabled()) {
			return FastEncrypt::decrypt($str);
		}

		return $str;
	}

	/**
	 * Unserializes data from a string. Throws an exception if the data cannot be read.
	 *
	 * @param string $data
	 * @return mixed
	 * @throws CookieInitializationException
	 */
	private function unserialize($data) {
		if (config('session.serialize', true)) {
			$result = @unserialize($data);

			if ($result === false && $data !== 'b:0;') {
				throw new CookieInitializationException();
			}

			return $result;
		}
		else {
			$result = @json_decode($data, true);

			if (is_null($result)) {
				throw new CookieInitializationException();
			}

			return $result;
		}
	}

	/**
	 * Serializes data into a string.
	 *
	 * @param mixed $object
	 * @return string
	 */
	private function serialize($object) {
		if (config('session.serialize', true)) {
			return serialize($object);
		}
		else {
			return json_encode($object);
		}
	}

	/**
	 * Rewrites the session payload and flash to exclude encryption.
	 */
	private function rewriteDisableEncryption() {
		// Decrypt flash data
		foreach ($this->newFlashDataRemote as $key => $value) {
			$this->newFlashDataRemote[$key] = FastEncrypt::decrypt($value);
		}

		// Decrypt payload
		foreach ($this->sessionDataRemote as $key => $value) {
			$this->sessionDataRemote[$key] = FastEncrypt::decrypt($value);
		}
	}

	/**
	 * Rewrites the session payload and flash to include encryption.
	 */
	private function rewriteEnableEncryption() {
		// Encrypt flash data
		foreach ($this->newFlashDataRemote as $key => $value) {
			$this->newFlashDataRemote[$key] = FastEncrypt::encrypt($value);
		}

		// Encrypt payload
		foreach ($this->sessionDataRemote as $key => $value) {
			$this->sessionDataRemote[$key] = FastEncrypt::encrypt($value);
		}
	}

	/**
	 * Deletes expired flash data from the session. This does not impact any of the accessible data from within the
	 * current flash.
	 */
	private function expireFlash() {
		if (!empty($this->newFlashDataRemote)) {
			$this->newFlashDataRemote = array();
			$this->commit();
		}
	}

	/**
	 * Updates the database with the latest session data.
	 *
	 * @return void
	 * @throws DatabaseException
	 * @throws HorizonException
	 * @throws Exception
	 */
	private function commit() {
		$this->model->data = $this->serialize([
			$this->isEncryptionEnabled(),
			$this->sessionDataRemote,
			$this->newFlashDataRemote,
			$this->csrfToken
		]);

		$this->model->expires_at = time() + config('session.lifetime', 3600);
		$this->model->save();
	}

}
