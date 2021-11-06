<?php

namespace Horizon\Encryption;

use Exception;
use phpseclib\Crypt\AES;
use phpseclib\Crypt\Random;

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
		$iv = self::generateIV();
		$cipher = self::generateCipher($iv);
		$data = $cipher->encrypt($data);

		return $iv . $data;
	}

	/**
	 * @param string $encrypted The encrypted data to decrypt.
	 */
	public static function decrypt($encrypted) {
		list($iv, $data) = self::extractIV($encrypted);

		$cipher = self::generateCipher($iv);
		return $cipher->decrypt($data);
	}

	/**
	 * Generates and returns the cipher AES object with the key and IV loaded.
	 *
	 * @return AES
	 */
	private static function generateCipher($iv) {
		$cipher = new AES();
		$blockLength = ($cipher->getBlockLength() >> 3);

		$cipher->setKey($blockLength, self::generateKey());
		$cipher->setIV($blockLength, $iv);

		return $cipher;
	}

	/**
	 * Generates a key to use for encryption.
	 *
	 * @param $length
	 * @return string Encryption key containing $length number of characters.
	 */
	private static function generateKey($length = 32) {
		if (env('APP_SECRET')) {
			return substr(hash('sha256', sprintf('FE:%s', env('APP_SECRET'))), 0, $length);
		}

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
	 * Generates a random IV for encryption.
	 *
	 * @param $length
	 * @return string Encryption IV containing $length number of characters.
	 */
	private static function generateIV($length = 32) {
		return Random::string($length);
	}

	/**
	 * Extracts the data and IV from a cipher.
	 *
	 * @param string $data
	 * @param int $length
	 * @return string[]
	 */
	private static function extractIV($data, $length = 32) {
		if (strlen($data) <= $length) {
			throw new Exception('Encrypted data is too short and may be lacking an initialization vector');
		}

		$iv = substr($data, 0, $length);
		$data = substr($data, $length);

		return [$iv, $data];
	}

}
