<?php

namespace Horizon\Encryption;

use phpseclib\Crypt\AES;

/**
 * A lightweight and fast symmetric key encryption with automatic key construction. This is meant for use in storage
 * of framework-generated, server-side data. If you wish to use symmetric key encryption for your own data, see the
 * SymmetricKey encryption class.
 */
class FastEncrypt {

	/**
	 * @param string $data The text to encrypt.
	 */
	public static function encrypt($data) {
		$cipher = self::generateCipher();
		return $cipher->encrypt($data);
	}

	/**
	 * @param string $encrypted The encrypted data to decrypt.
	 */
	public static function decrypt($encrypted) {
		$cipher = self::generateCipher();
		return $cipher->decrypt($encrypted);
	}

	/**
	 * Generates and returns the cipher AES object with the key and IV loaded.
	 *
	 * @return AES
	 */
	private static function generateCipher() {
		$cipher = new AES();
		$blockLength = ($cipher->getBlockLength() >> 3);

		$cipher->setKey($blockLength, self::generateKey());
		$cipher->setIV($blockLength, self::generateIV());

		return $cipher;
	}

	/**
	 * Generates a relatively secure key based on the server's configuration and the framework's environment.
	 *
	 * @param $length
	 * @return string Encryption key containing $length number of characters.
	 */
	private static function generateKey($length = 32) {
		$base = substr(md5(__DIR__), 0, 10);

		if (isset($_SERVER['PATH'])) $base .= substr(md5($_SERVER['PATH']), 2, 8);
		if (isset($_SERVER['TMP'])) $base .= substr(md5($_SERVER['TMP']), 2, 8);
		if (function_exists('curl_version')) $base .= substr(md5(curl_version()['version_number']), 2, 4);
		if (@ini_get('max_execution_time')) $base .= substr(md5(ini_get('max_execution_time')), 2, 4);
		if (@ini_get('log_errors_max_len')) $base .= substr(md5(ini_get('log_errors_max_len')), 2, 4);

		$base .= md5($base);

		$first = substr(md5(substr($base, 0, (strlen($base) / 2))), 0, floor($length / 2));
		$last = substr(md5(substr($base, (strlen($base) / 2))), 0, ceil($length / 2));

		return $first . $last;
	}

	/**
	 * Generates an IV based on the server's configuration and the framework's environment.
	 *
	 * @param $length
	 * @return string Encryption IV containing $length number of characters.
	 */
	private static function generateIV($length = 32) {
		$base = substr(md5(__DIR__), 0, 5);

		if (@ini_get('extension_dir')) $base .= substr(md5(ini_get('extension_dir')), 2, 4);
		if (@ini_get('error_reporting')) $base .= substr(md5(ini_get('error_reporting')), 2, 4);
		if (@ini_get('realpath_cache_size')) $base .= substr(md5(ini_get('realpath_cache_size')), 2, 4);
		if (function_exists('curl_version')) $base .= substr(md5(json_encode(curl_version())), 2, 4);

		$first = substr(md5(substr($base, 0, (strlen($base) / 2))), 0, floor($length / 2));
		$last = substr(md5(substr($base, (strlen($base) / 2))), 0, ceil($length / 2));

		return $first . $last;
	}

}
