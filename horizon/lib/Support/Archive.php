<?php

namespace Horizon\Support;

use Horizon\Encryption\FastEncrypt;
use Horizon\Foundation\Framework;

class Archive
{
	/**
	 * Compressed data.
	 */
	protected $datasec = array();

	/**
	 * Uncompressed files.
	 *
	 * @var array[]
	 */
	protected $files = array(); // array of uncompressed files

	/**
	 * Directories that have been created.
	 *
	 * @var array[]
	 */
	protected $dirs = array();

	/**
	 * Central directory.
	 *
	 * @var array
	 */
	protected $ctrlDir = array();

	/**
	 * End of central directory record.
	 *
	 * @var string
	 */
	protected $eofCtrlDir = "\x50\x4b\x05\x06\x00\x00\x00\x00";

	/**
	 * The current offset of the central directory record.
	 *
	 * @var int
	 */
	protected $oldOffset = 0;

	/**
	 * Base directory for files in the archive.
	 *
	 * @var string
	 */
	protected $baseDir = ".";

	/**
	 * Error when parsing the archive.
	 *
	 * @var string
	 */
	protected $archiveError = '';

	/**
	 * @var string|null
	 */
	protected $localEncryption;

	/**
	 * Constructs a new Archive instance.
	 *
	 * @param string|null $path
	 * @param string $baseDir
	 * @param string $raw
	 */
	public function __construct($path = null, $baseDir = '.', $raw = null)
	{
		$this->baseDir = $baseDir;

		if (!is_null($path)) {
			$this->readZip($path);
		}

		if (!is_null($raw)) {
			$this->readZip(null, $raw);
		}
	}

	/**
	 * Adds a directory to the archive with the specified name.
	 *
	 * @param string $name
	 */
	public function createDirectory($name)
	{
		$name = str_replace('\\', '', $name);

		// Local file header
		$fr  = "\x50\x4b\x03\x04";
		$fr .= "\x0a\x00";
		$fr .= "\x00\x00";
		$fr .= "\x00\x00";
		$fr .= "\x00\x00\x00\x00";

		// Local file descriptor
		$fr .= pack('v', 0);
		$fr .= pack('v', 0);
		$fr .= pack('v', 0);
		$fr .= pack('v', strlen($name));
		$fr .= pack('v', 0);
		$fr .= $name;

		// Data descriptor
		$fr .= pack('V', 0);
		$fr .= pack('V', 0);
		$fr .= pack('V', 0);

		$this->datasec[] = $fr;
		$new_offset = strlen(implode('', $this->datasec));

		// Central record header
		$cdrec  = "\x50\x4b\x01\x02";
		$cdrec .= "\x00\x00";
		$cdrec .= "\x0a\x00";
		$cdrec .= "\x00\x00";
		$cdrec .= "\x00\x00";
		$cdrec .= "\x00\x00\x00\x00";

		// Central record descriptor
		$cdrec .= pack('v', 0);
		$cdrec .= pack('v', 0);
		$cdrec .= pack('v', 0);
		$cdrec .= pack('v', strlen($name));
		$cdrec .= pack('v', 0);
		$cdrec .= pack('v', 0);
		$cdrec .= pack('v', 0);
		$cdrec .= pack('v', 0);
		$cdrec .= pack('V', 16);

		$cdrec .= pack('V', $this->oldOffset);
		$this->oldOffset = $new_offset;

		$cdrec .= $name;

		$this->ctrlDir[] = $cdrec;
		$this->dirs[] = $name;
	}

	/**
	 * Adds a file to the archive at the specified path.
	 *
	 * @param string $data
	 * @param string $name
	 */
	public function createFile($data, $name)
	{
		$name = str_replace('\\', '/', $name);

		// Local file header
		$fr  = "\x50\x4b\x03\x04";
		$fr .= "\x14\x00";
		$fr .= "\x00\x00";
		$fr .= "\x08\x00";
		$fr .= "\x00\x00\x00\x00";

		$unc_len = strlen($data);
		$crc = crc32($data);
		$zdata = gzcompress($data);
		$zdata = substr($zdata, 2, -4);
		$c_len = strlen($zdata);

		// Local file descriptor
		$fr .= pack('V', $crc);
		$fr .= pack('V', $c_len);
		$fr .= pack('V', $unc_len);
		$fr .= pack('v', strlen($name));
		$fr .= pack('v', 0);
		$fr .= $name;
		$fr .= $zdata;

		// Data descriptor
		$fr .= pack('V', $crc);
		$fr .= pack('V', $c_len);
		$fr .= pack('V', $unc_len);

		$this->datasec[] = $fr;
		$new_offset = strlen(implode("", $this->datasec));

		// Add to central directory record
		$cdrec = "\x50\x4b\x01\x02";
		$cdrec .="\x00\x00";
		$cdrec .="\x14\x00";
		$cdrec .="\x00\x00";
		$cdrec .="\x08\x00";
		$cdrec .="\x00\x00\x00\x00";

		$cdrec .= pack('V', $crc);
		$cdrec .= pack('V', $c_len);
		$cdrec .= pack('V', $unc_len);
		$cdrec .= pack('v', strlen($name));
		$cdrec .= pack('v', 0);
		$cdrec .= pack('v', 0);
		$cdrec .= pack('v', 0);
		$cdrec .= pack('v', 0);
		$cdrec .= pack('V', 32);

		// Relative offset of local file header
		$cdrec .= pack('V', $this->oldOffset);

		$this->oldOffset = $new_offset;
		$this->ctrlDir[] = $cdrec . $name;
	}

	/**
	 * Reads the specified zip archive.
	 *
	 * @param string $name
	 * @param string|null $raw
	 */
	protected function readZip($name, $raw = null)
	{
		// Clear current file
		$this->datasec = array();

		if (is_null($raw)) {
			// File information
			$this->name = $name;
			$this->mtime = filemtime($name);
			$this->size = filesize($name);

			// Read file
			$fh = fopen($name, 'rb');
			$filedata = fread($fh, $this->size);
			fclose($fh);
		}
		else {
			// File information
			$this->name = 'raw_archive';
			$this->mtime = time();
			$this->size = strlen($raw);

			// Read file
			$filedata = $raw;
		}

		// Break into sections
		$filesecta = explode("\x50\x4b\x05\x06", $filedata);

		if (!isset($filesecta[1])) {
			$this->archiveError = 'Could not parse archive: invalid format';
			return;
		}

		// Zip comment
		$unpackeda = unpack('x16/v1length', $filesecta[1]);
		$this->comment = substr($filesecta[1], 18, $unpackeda['length']);
		$this->comment = str_replace(array("\r\n", "\r"), "\n", $this->comment);

		// Cut entries from the central directory
		$filesecta = explode("\x50\x4b\x01\x02", $filedata);
		$filesecta = explode("\x50\x4b\x03\x04", $filesecta[0]);

		// Remove empty entry/signature
		array_shift($filesecta);

		foreach ($filesecta as $filedata)
		{
			$entrya = array();
			$entrya['error'] = "";

			$unpackeda = unpack('v1version/v1general_purpose/v1compress_method/v1file_time/v1file_date/V1crc/V1size_compressed/V1size_uncompressed/v1filename_length', $filedata);

			// Check for encryption
			$isEncrypted = (($unpackeda['general_purpose'] & 0x0001) ? true : false);

			// Check for value block after compressed data
			if ($unpackeda['general_purpose'] & 0x0008)
			{
				$unpackeda2 = unpack('V1crc/V1size_compressed/V1size_uncompressed', substr($filedata, -12));

				$unpackeda['crc'] = $unpackeda2['crc'];
				$unpackeda['size_compressed'] = $unpackeda2['size_uncompressed'];
				$unpackeda['size_uncompressed'] = $unpackeda2['size_uncompressed'];

				unset($unpackeda2);
			}

			$entrya['name'] = substr($filedata, 26, $unpackeda['filename_length']);

			if (substr($entrya['name'], -1) == '/') {
				continue;
			}

			$entrya['dir'] = dirname($entrya['name']);
			$entrya['dir'] = ($entrya['dir'] == '.' ? '' : $entrya['dir']);
			$entrya['name'] = basename($entrya['name']);

			if ($entrya['dir'] != '') {
				$bp = '';
				$dirpieces = explode('/', $entrya['dir']);

				foreach ($dirpieces as $p) {
					$bp .= $p . '/';
					$dname = substr($bp, 0, -1);

					if (!in_array($dname, $this->dirs)) {
						$this->dirs[] = $dname;
					}
				}
			}

			$filedata = substr($filedata, 26 + $unpackeda['filename_length']);

			if (strlen($filedata) != $unpackeda['size_compressed']) {
				$entrya['error'] = 'Compressed size is not equal to the value given in header.';
			}

			if ($isEncrypted) {
				$entrya['error'] = "Encryption is not supported.";
			}
			else {
				switch($unpackeda['compress_method']) {
					case 0:
						break;
					case 8:
						$filedata = gzinflate($filedata);
						break;
					case 12:
						if (!extension_loaded("bz2")) {
							@dl((strtolower(substr(PHP_OS, 0, 3)) == 'win') ? 'php_bz2.dll' : 'bz2.so');
						}

						if (extension_loaded("bz2")) {
							$filedata = bzdecompress($filedata);
						}
						else {
							$entrya['error'] = 'Cannot parse archive without the BZip2 extension, which is not available.';
						}

						break;
					default:
						$entrya['error'] = "Compression method ({$unpackeda['compress_method']}) not supported.";
				}

				if (!$entrya['error']) {
					if ($filedata === false) {
						$entrya['error'] = 'Decompression failed.';
					}
					else if (strlen($filedata) != $unpackeda['size_uncompressed']) {
						$entrya['error'] = 'File size is not equal to the value given in header.';
					}
					else if(crc32($filedata) != $unpackeda['crc']) {
						$entrya['error'] = 'CRC32 checksum is not equal to the value given in header.';
					}
				}

				// Build modification time
				$entrya['filemtime'] = mktime(
					($unpackeda['file_time'] & 0xf800) >> 11,
					($unpackeda['file_time'] & 0x07e0) >> 5,
					($unpackeda['file_time'] & 0x001f) << 1,
					($unpackeda['file_date'] & 0x01e0) >> 5,
					($unpackeda['file_date'] & 0x001f),
					(($unpackeda['file_date'] & 0xfe00) >> 9) + 1980
				);

				$entrya['data'] = $filedata;
			}

			$this->files[] = $entrya;
		}

		return $this->files;
	}

	public function addFile($file, $dir = '.', $file_blacklist = array(), $ext_blacklist = array())
	{
		$file = str_replace('\\', '/', $file);
		$dir = str_replace('\\', '/', $dir);

		if (strpos($file, '/') !== false) {
			$tmpPath = explode('/', $dir . '/' . $file);
			$file = array_shift($tmpPath);
			$dir = implode('/', $tmpPath);

			unset($tmpPath);
		}

		while (substr($dir, 0, 2) == './') {
			$dir = substr($dir, 2);
		}

		while (substr($file, 0, 2) == './') {
			$file = substr($file, 2);
		}

		if (!in_array($dir, $this->dirs)) {
			if ($dir == '.') {
				$this->createDirectory('./');
			}

			$this->dirs[] = $dir;
		}

		if (in_array($file, $file_blacklist)) {
			return true;
		}

		foreach ($ext_blacklist as $ext) {
			if (substr($file, -1 - strlen($ext)) == ('.' . $ext)) {
				return true;
			}
		}

		$filepath = (($dir && $dir != '.') ? ($dir . '/') : '') . $file;

		if (is_dir($this->baseDir . '/' . $filepath)) {
			$dh = opendir($this->baseDir . '/' . $filepath);

			while (($subfile = readdir($dh)) !== false) {
				if ($subfile != '.' && $subfile != '..') {
					$this->addFile($subfile, $filepath, $file_blacklist, $ext_blacklist);
				}
			}

			closedir($dh);
		}
		else {
			$this->createFile(implode('', file($this->baseDir . '/' . $filepath)), $filepath);
		}

		return true;
	}

	/**
	 * Gets the files in the archive.
	 *
	 * @return array[]
	 */
	public function getFiles()
	{
		return $this->files;
	}

	/**
	 * Gets the directories in the archive.
	 *
	 * @return array[]
	 */
	public function getDirectories()
	{
		return $this->dirs;
	}

	/**
	 * Gets whether or not this archive errored during parsing.
	 *
	 * @return bool
	 */
	public function hasError()
	{
		return !empty($this->archiveError);
	}

	/**
	 * Gets the archive error, or a blank string if no error occurred during parsing.
	 *
	 * @return string
	 */
	public function getError()
	{
		return $this->archiveError;
	}

	/**
	 * Checks whether the archive's contents are encrypted.
	 *
	 * @return bool
	 */
	public function isEncrypted()
	{
		if (!is_null($this->localEncryption)) return true;

		foreach ($this->files as $file) {
			if (empty($file['dir']) && $file['name'] == 'HZENCRYPTED') {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks whether the archive's contents can be decrypted.
	 *
	 * @return bool
	 */
	public function isDecryptable()
	{
		if (!is_null($this->localEncryption)) {
			$result = FastEncrypt::decrypt($this->localEncryption);

			if (strpos($result, ';') !== false) {
				$split = explode(';', $result);

				if (array_shift($split) == Framework::path()) {
					return true;
				}
			}
		}

		foreach ($this->files as $file) {
			if (empty($file['dir']) && $file['name'] == 'HZENCRYPTED') {
				$data = trim($file['data']);
				$result = FastEncrypt::decrypt($data);

				if (strpos($result, ';') !== false) {
					$split = explode(';', $result);

					if (array_shift($split) == Framework::path()) {
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Marks the archive as encrypted. Note that this does not automatically encrypt the data -- that is your
	 * responsibility.
	 */
	public function markEncrypted()
	{
		foreach ($this->files as $file) {
			if (empty($file['dir']) && $file['name'] == 'HZENCRYPTED') {
				return;
			}
		}

		$cipher = FastEncrypt::encrypt(Framework::path() . ';' . time() . ';' . Framework::version());
		$this->createFile($cipher, 'HZENCRYPTED');
		$this->localEncryption = $cipher;
	}

	/**
	 * Builds the archive contents and returns the compressed string.
	 *
	 * @return string
	 */
	public function build()
	{
		$data = implode('', $this->datasec);
		$ctrldir = implode('', $this->ctrlDir);

		return (
			$data.
			$ctrldir .
			$this->eofCtrlDir .
			pack('v', sizeof($this->ctrlDir)) .
			pack('v', sizeof($this->ctrlDir)) .
			pack('V', strlen($ctrldir)) .
			pack('V', strlen($data)) .
			"\x00\x00"
		);
	}

	/**
	 * Constructs a new archive from raw data in a string.
	 *
	 * @param string $raw
	 *
	 * @return Archive
	 */
	public static function fromString($raw)
	{
		return new Archive(null, '.', $raw);
	}

	public function __toString()
	{
		return $this->build();
	}
}
