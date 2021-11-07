<?php

namespace Horizon\Ace\Commands\Migrations;

use Horizon\Console\Command;
use Horizon\Foundation\Application;
use Horizon\Support\Facades\Ace;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrationFreshCommand extends Command {

	/**
	 * Configures the command.
	 */
	protected function configure() {
		$this->setDescription('Deletes all tables and runs migrations from the beginning');
	}

	/**
	 * Executes the command.
	 */
	protected function execute(InputInterface $in, OutputInterface $out) {
		$out->writeln('<fg=blue>[drop all tables]</>');
		$this->clearTables($out);
		$out->writeln('');

		$out->writeln('<fg=blue>[run migrations]</>');
		Ace::run('migration:run', [], $out);
	}

	/**
	 * Drops all tables in all registered databases. Dangerous...
	 *
	 * @return void
	 */
	protected function clearTables(OutputInterface $out) {
		$numTables = 0;

		foreach (Application::kernel()->database()->getConnections() as $connectionName => $connection) {
			$database = $connection->getDatabase()->getDatabaseName();

			$rows = $connection->query('SHOW TABLES');
			$connection->query('SET FOREIGN_KEY_CHECKS = 0;');

			foreach ($rows as $row) {
				$numTables++;
				$tableName = array_values((array) $row)[0];
				$out->writeln(sprintf(
					'<fg=yellow>[ᐅ]</> [%s] %s.%s',
					$connectionName,
					$database,
					$tableName
				));

				$connection->query('DROP TABLE IF EXISTS `' . $tableName . '`;');
			}

			$connection->query('SET FOREIGN_KEY_CHECKS = 1;');
		}

		$out->writeln('<fg=green>[✓]</> dropped tables');
	}

}
