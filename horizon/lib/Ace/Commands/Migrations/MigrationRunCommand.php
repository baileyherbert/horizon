<?php

namespace Horizon\Ace\Commands\Migrations;

use Horizon\Console\Command;
use Horizon\Database\Migration;
use Horizon\Database\Migration\SchemaRecorderBucket;
use Horizon\Database\Migrator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrationRunCommand extends Command {

	/**
	 * Configures the command.
	 */
	protected function configure() {
		$this->setDescription('Runs pending migrations');
		$this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Prints queries instead of executing them');
	}

	/**
	 * Builds and returns the migrator instance to use .
	 *
	 * @return Migrator
	 */
	protected function getMigrator(InputInterface $in) {
		$migrator = new Migrator();
		$migrator->dryRun = $in->getOption('dry-run');

		return $migrator;
	}

	/**
	 * Runs all pending migrations.
	 *
	 * @param InputInterface $in
	 * @param OutputInterface $out
	 * @return void
	 */
	protected function execute(InputInterface $in, OutputInterface $out) {
		$migrator = $this->getMigrator($in);
		$count = count($migrator->getPendingMigrations());

		if ($count === 0) {
			return $out->writeln('<fg=green>[✓]</> nothing to do');
		}

		// Print the migrations as we start them
		$migrator->on('migration:start', function(Migration $migration) use ($migrator, $out) {
			$verb = $migrator->dryRun ? 'test' : 'migrate';
			$out->write("<fg=yellow>[ᐅ]</> $verb <fg=white>{$migration->getRecordName()}</> ");

			// For dry runs, the next output will be captured queries, so push a newline now
			if ($migrator->dryRun) {
				$out->writeln('');
			}
		});

		// Print the time it takes migrations to finish
		$migrator->on('migration:finish', function(Migration $migration, $took) use ($migrator, $out) {
			if (!$migrator->dryRun) {
				if ($took >= 1) $took = round($took);
				else if ($took >= 0.1) $took = round($took, 1);
				else if ($took >= 0.01) $took = round($took, 2);

				$out->writeln("($took ms)");
			}
			else {
				$this->drawSchemaBucket($migration->getBucket(), $out);
			}
		});

		// Run all migrations and retrieve the batch
		$batch = $migrator->run();

		$plural = $count !== 1 ? 's' : '';
		$out->writeln($migrator->dryRun ?
			"<fg=green>[✓]</> all migrations passed" :
			"<fg=green>[✓]</> committed {$count} migration{$plural} as batch #{$batch->getId()}"
		);
	}

	/**
	 * Lists statements and errors in the output for a dry run.
	 *
	 * @param SchemaRecorderBucket $bucket
	 * @param OutputInterface $out
	 * @return void
	 */
	private function drawSchemaBucket(SchemaRecorderBucket $bucket, OutputInterface $out) {
		$statements = $bucket->all();

		foreach ($statements as $statement) {
			$exception = $statement->getException();
			$color = $exception ? 'red' : 'green';

			$out->writeln(sprintf(
				'  <fg=%s>→  intercept (%s):</> %s',
				$color,
				$statement->getConnectionName(),
				$statement->getQuery()
			));

			if ($exception) {
				$out->writeln(sprintf('  <fg=red>→  caught: %s</>', $exception->getMessage()));
			}
		}

		if (empty($statement)) {
			$out->writeln('  <fg=red>→  no intercepts</> were captured for this migration');
		}

		$out->writeln('');
	}

}
