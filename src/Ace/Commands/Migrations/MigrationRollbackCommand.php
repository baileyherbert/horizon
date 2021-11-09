<?php

namespace Horizon\Ace\Commands\Migrations;

use Horizon\Console\Command;
use Horizon\Database\Migration;
use Horizon\Database\Migration\MigrationBatch;
use Horizon\Database\Migrator;
use Horizon\Support\Arr;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrationRollbackCommand extends Command {

	/**
	 * Configures the command.
	 */
	protected function configure() {
		$this->setDescription('Rolls back migrations to a given batch number');

		$this->addOption('batch', 'b', InputOption::VALUE_REQUIRED, 'Roll back to the specified batch number');
		$this->addOption('step', 's', InputOption::VALUE_REQUIRED, 'Roll back by the specified number of batches');
		$this->addOption('reset', 'r', InputOption::VALUE_NONE, 'Roll back all migrations');
	}

	/**
	 * Executes the command.
	 */
	protected function execute(InputInterface $in, OutputInterface $out) {
		$migrator = $this->getMigrator($in);
		$batches = $this->getBatches($in, $migrator);
		$count = count($batches);
		$migrations = 0;

		if ($count === 0) {
			return $out->writeln('<fg=green>[✓]</> nothing to do');
		}

		// Print the migrations as we start them
		$migrator->on('migration:start', function(Migration $migration) use ($migrator, $out) {
			$out->write("<fg=yellow>[ᐅ]</> rollback {$migration->getRecordName()} ");
		});

		// Print the time it takes migrations to finish
		$migrator->on('migration:finish', function(Migration $migration, $took) use ($migrator, $out) {
			if (!$migrator->dryRun) {
				if ($took >= 1) $took = round($took);
				else if ($took >= 0.1) $took = round($took, 1);
				else if ($took >= 0.01) $took = round($took, 2);

				$out->writeln("($took ms)");
			}
		});

		foreach ($batches as $batch) {
			$out->writeln(sprintf('<fg=yellow>[ᐅ]</> rollback batch #%d', $batch->getId()));
			$migrations += count($batch->getMigrations());
			$batch->rollback();
		}

		$out->writeln(sprintf(
			'<fg=green>[✓]</> reverted %d %s (%d %s)',
			$count,
			str_plural('batch', $count),
			$migrations,
			str_plural('migration', $migrations)
		));
	}

	/**
	 * Builds and returns the migrator instance to use .
	 *
	 * @return Migrator
	 */
	protected function getMigrator(InputInterface $in) {
		$migrator = new Migrator();
		$migrator->direction = Migrator::DIRECTION_DOWN;
		// $migrator->dryRun = $in->getOption('dry-run');

		return $migrator;
	}

	/**
	 * Returns an array of batches to roll back based on input options. The array will be sorted with the most recent
	 * batch first.
	 *
	 * @param InputInterface $in
	 * @param Migrator $migrator
	 * @return MigrationBatch[]
	 */
	protected function getBatches(InputInterface $in, Migrator $migrator) {
		$batches = array_reverse(Arr::sort($migrator->getBatches(), function($batch) {
			return $batch->getId();
		}));

		// Roll back to the specified batch number (not including the specified batch itself)
		if ($in->getOption('batch')) {
			$targetBatchId = $this->getOptionAsInteger($in, 'batch');

			$batches = array_filter($batches, function($batch) use ($targetBatchId) {
				return $batch->getId() > $targetBatchId;
			});

			return $batches;
		}

		// Roll back the specified number of batches
		if ($in->getOption('step')) {
			$step = $this->getOptionAsInteger($in, 'step');

			if ($step > count($batches)) {
				throw new InvalidOptionException('The value specified for "step" is greater than the number of batches');
			}

			return array_slice($batches, 0, $step);
		}

		// Roll back all migrations
		if ($in->getOption('reset')) {
			return $batches;
		}

		// Default to the latest batch
		if (count($batches) > 0) {
			return array($batches[0]);
		}

		return array();
	}

	/**
	 * Returns the value of the specified option (by name) as an integer, or throws an exception if it's invalid.
	 *
	 * @param InputInterface $in
	 * @param string $name
	 * @return int
	 */
	protected function getOptionAsInteger(InputInterface $in, $name) {
		$value = $in->getOption($name);

		if (!preg_match("/^\d+$/", $value)) {
			throw new InvalidOptionException(sprintf('Option "%s" must be an integer', $name));
		}

		return intval($value);
	}

}
