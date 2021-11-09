<?php

namespace Horizon\Ace\Commands\Migrations;

use Horizon\Ace\Util\AsciiTable;
use Horizon\Ace\Util\TimeAgo;
use Horizon\Console\Command;
use Horizon\Database\Migration;
use Horizon\Database\Migrator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrationStatusCommand extends Command {

	/**
	 * @var Migrator
	 */
	private $migrator;

	/**
	 * Configures the command.
	 */
	protected function configure() {
		$this->migrator = new Migrator();

		$this->setDescription('Checks the current status of migrations');
		$this->addOption('json', null, InputOption::VALUE_NONE, 'Returns output in JSON format.');
	}

	/**
	 * Executes the command.
	 */
	protected function execute(InputInterface $in, OutputInterface $out) {
		if ($in->getOption('json')) {
			$out->writeln(json_encode(array_merge(
				$this->migrator->getFinishedMigrations(),
				$this->migrator->getPendingMigrations()
			)));
		}
		else {
			$this->showAllMigrations($out);
		}
	}

	/**
	 * Renders all migrations, ordered by finished first and pending last, to the output in a table.
	 *
	 * @param OutputInterface $out
	 * @return void
	 */
	public function showAllMigrations(OutputInterface $out) {
		$this->showMigrations($out, array_merge(
			$this->migrator->getFinishedMigrations(),
			$this->migrator->getPendingMigrations()
		));
	}

	/**
	 * Renders all pending migrations to the output in a table.
	 *
	 * @param OutputInterface $out
	 * @return void
	 */
	public function showPendingMigrations(OutputInterface $out) {
		$this->showMigrations($out, $this->migrator->getPendingMigrations());
	}

	/**
	 * Renders all finished migrations to the output in a table.
	 *
	 * @param OutputInterface $out
	 * @return void
	 */
	public function showFinishedMigrations(OutputInterface $out) {
		$this->showMigrations($out, $this->migrator->getFinishedMigrations());
	}

	/**
	 * Draws the given migration array to the output in a table.
	 *
	 * @param OutputInterface $out
	 * @param Migration[] $migrations
	 * @return void
	 */
	public function showMigrations(OutputInterface $out, array $migrations) {
		$table = new AsciiTable();
		$table->cellSpacing = 4;

		$table->addColumn('id');
		$table->addColumn('migration');
		$table->addColumn('batch');
		$table->addColumn('status');
		$table->addColumn('time', 12);

		$nextMigrationId = 1;
		$nextBatchId = 1;

		foreach ($migrations as $migration) {
			$id = $migration->getRecordId();
			$name = $migration->getRecordName();
			$batchId = $migration->getBatchId();
			$status = $migration->isPending() ? '<fg=yellow>✖  pending</>' : '<fg=green>✔  finished</>';
			$time = $this->getMigrationTimestamp($migration);

			if ($migration->isFinished()) {
				$nextMigrationId = max($nextMigrationId, $id + 1);
				$nextBatchId = max($nextBatchId, $batchId + 1);
			}

			else {
				$id = $nextMigrationId++;
				$batchId = $nextBatchId;
			}

			$table->addRow([
				$id,
				$name,
				$batchId,
				$status,
				$time
			]);
		}

		$table->render($out);
	}

	/**
	 * Returns a human friendly timestamp for the given migration.
	 *
	 * @param Migration $migration
	 * @return string
	 */
	public function getMigrationTimestamp(Migration $migration) {
		if ($migration->isFinished()) {
			if ($migration->getTime() > (time() - (86400 * 28))) {
				return (new TimeAgo($migration->getTime()))->get();
			}
			else {
				return timestamp_to_datetime($migration->getTime());
			}
		}

		return '';
	}

}
