<?php

namespace Horizon\Database;

use Horizon\Foundation\Application;
use Horizon\Support\Services\ServiceProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Provides routes for the request to be matched against.
 */
class MigrationServiceProvider extends ServiceProvider {

	public function register() {
		$this->bind('Horizon\Database\Migration', function() {
			$path = Application::paths()->migrations();
			$migrations = [];

			if (is_dir($path)) {
				$dirIterator = new RecursiveDirectoryIterator($path);
				$fileIterator = new RecursiveIteratorIterator($dirIterator);

				foreach ($fileIterator as $file) {
					if ($file->isDir()) continue;
					if (!ends_with($file->getFilename(), '.php')) continue;
					if (!preg_match("/^\d+_(.+)$/", $file->getFileName())) continue;

					$fileName = $file->getFileName();
					$filePath = $file->getRealPath();

					$timestampSeparatorIndex = strpos($fileName, '_');
					$timestamp = substr($fileName, 0, $timestampSeparatorIndex);
					$className = 'Migration_' . $timestamp;

					require $filePath;
					$migrations[] = new $className($filePath);
				}
			}

			return $migrations;
		});
	}

	public function provides() {
		return array(
			'Horizon\Database\Migration'
		);
	}

}
