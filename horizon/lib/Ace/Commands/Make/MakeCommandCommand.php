<?php

namespace Horizon\Ace\Commands\Make;

use Horizon\Ace\Util\FileGenerator;
use Horizon\Console\Command;
use Horizon\Foundation\Framework;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MakeCommandCommand extends Command {

	/**
	 * Configures the command.
	 */
	protected function configure() {
		$this->setDescription('Makes a new command file');
		$this->addArgument('name', InputArgument::REQUIRED, 'The name of the command.');
		$this->addOption('root', 'r', InputOption::VALUE_NONE, 'Uses the root source directory.');
		$this->addOption('open', 'o', InputOption::VALUE_NONE, 'Opens the file with your default PHP editor.');
	}

	/**
	 * Executes the command.
	 */
	protected function execute(InputInterface $in, OutputInterface $out) {
		$generator = new FileGenerator($in->getArgument('name'));
		$generator->namespace = $in->getOption('root') ? 'App' : 'App/Console/Commands';
		$generator->baseDir = $in->getOption('root') ? 'app/src' : 'app/src/Console/Commands';
		$generator->classNameSuffix = 'Command';
		$generator->enableColonNamespacing = true;

		$generator->assertClassName(['Command', 'InputInterface', 'OutputInterface']);
		$generator->renderClassFile('make/command', [], $out);

		$this->addCommandToConfig($generator->getFileName(), $generator->getClass(), $out);

		if ($in->getOption('open')) {
			exec('start ' . $generator->resolveClassPath());
		}
	}

	/**
	 * Works some unbelievably nasty "magic" to add the command to the console.php configuration file, and sort the
	 * commands in that file alphabetically...
	 *
	 * @param string $commandName
	 * @param string $className
	 * @param OutputInterface $out
	 * @return void
	 */
	private function addCommandToConfig($commandName, $className, OutputInterface $out) {
		$configPath = Framework::path('app/config/console.php');
		$content = file_get_contents($configPath);
		$expression = "/^([\t ]*)(['\"])commands(?:['\"]) => (?:array\(|\[) *([^\]\)]*)[\]\)](,?)/ms";

		if (!preg_match_all($expression, $content, $matches)) {
			return $out->writeln('<fg=red>[×]</> couldn\'t add command to app/config/console.php');
		}

		$indentation = $matches[1][0];
		$quoteCharacter = $matches[2][0];
		$commandLinesRaw = trim($matches[3][0]);
		$trailingComma = $matches[4][0];
		$newLines = [];

		preg_match_all(
			"/^(?:[\t ]*)['\"]([^'\"]+)['\"][\t ]*=>[\t ]*['\"]([^\t ]*)['\"],?/m",
			$commandLinesRaw,
			$commandLines,
			PREG_SET_ORDER
		);

		foreach ($commandLines as $line) {
			$lineName = $line[1];
			$lineClassName = $line[2];

			$newLines[$lineName] = implode('', [
				str_repeat($indentation, 2), $quoteCharacter, $lineName, $quoteCharacter, ' => ',
				$quoteCharacter, $lineClassName, $quoteCharacter, ','
			]);
		}

		$newLines[$commandName] = implode('', [
			str_repeat($indentation, 2), $quoteCharacter, $commandName, $quoteCharacter, ' => ',
			$quoteCharacter, $className, $quoteCharacter, ','
		]);

		ksort($newLines);

		$newArray = implode('', [
			$indentation, $quoteCharacter, 'commands', $quoteCharacter, " => [\n",
			rtrim(implode("\n", $newLines), ','),
			"\n", $indentation, ']', $trailingComma
		]);

		$content = preg_replace($expression, $newArray, $content);
		file_put_contents($configPath, $content);

		$out->writeln("<fg=green>[✓]</> append app/config/console.php");
	}

}
