<?php

namespace Horizon\Ace\Util;

class FileOpener {

	/**
	 * Opens a file with the default editor.
	 *
	 * @param string $path
	 * @return void
	 */
	public static function open($path) {
		$command = static::getCommand($path);
		exec($command);
	}

	/**
	 * Gets the command to open a file with the default editor.
	 *
	 * @param string $path
	 * @return string
	 */
	protected static function getCommand($path) {
		if (static::isWindows()) {
			return "start $path";
		}

		$termProgram = getenv('TERM_PROGRAM');

		if ($termProgram === 'vscode') {
			return "code $path";
		}

		return "nano $path";
	}

	protected static function isWindows() {
		return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
	}

}
