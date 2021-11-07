<?php

namespace Horizon\Ace\Commands\Make;

use Horizon\Console\Command;
use Horizon\Foundation\Framework;
use Horizon\Support\Path;
use Horizon\View\Template;
use Symfony\Component\Console\Exception\RuntimeException;
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

		$this->addArgument(
			'name',
			InputArgument::REQUIRED,
			'The name of the command.'
		);

		$this->addOption(
			'root',
			'r',
			InputOption::VALUE_NONE,
			'Uses the root source directory.'
		);

		$this->addOption(
			'open',
			'o',
			InputOption::VALUE_NONE,
			'Opens the file with your default PHP editor.'
		);
	}

	/**
	 * Executes the command.
	 */
	protected function execute(InputInterface $in, OutputInterface $out) {
		$name = $this->getNormalizedName($in->getArgument('name'));
		$commandName = $this->getCommandLineName($in->getArgument('name'));
		$className = Path::basename($name);

		if (in_array($className, ['Command', 'InputInterface', 'OutputInterface'])) {
			throw new RuntimeException('Cannot use reserved class name "' . $className . '"');
		}

		$filePath = Path::resolve(
			Framework::path($in->getOption('root') ? 'app/src' : 'app/src/Console/Commands'),
			"$name.php"
		);

		$dirPath = Path::dirname($filePath);
		$filePathRelative = str_replace('\\', '/', substr($filePath, strlen(Framework::path()) + 1));

		$fullClassName = ($in->getOption('root') ? 'App/' : 'App/Console/Commands/') . $name;
		$fullClassName = str_replace('/', '\\', $fullClassName);
		$className = str_replace('/', '\\', Path::basename($fullClassName));
		$namespace = str_replace('/', '\\', Path::dirname($fullClassName));

		if (!file_exists($dirPath)) {
			mkdir($dirPath, 0755, true);
		}

		if (file_exists($filePath)) {
			throw new RuntimeException('File conflict: ' . $filePath);
		}

		$result = file_put_contents($filePath, $this->getTemplate([
			'name' => $className,
			'namespace' => $namespace
		]));

		$out->writeln("<fg=green>[✓]</> create $filePathRelative");

		if ($result === false) {
			throw new RuntimeException('Error writing file: ' . $filePath);
		}

		$this->addCommandToConfig($commandName, $fullClassName, $out);

		if ($in->getOption('open')) {
			exec('start ' . $filePath);
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

	/**
	 * Returns the migration name as a normalized string for use in the file name.
	 *
	 * @param string $name
	 * @return string
	 */
	protected function getNormalizedName($name) {
		$name = str_replace('\\', '/', $name);
		$words = array_map('ucfirst', explode(':', $name));
		$name = trim(trim(implode('', $words)), '/');

		if (!ends_with($name, 'command', true)) {
			$name .= 'Command';
		}

		return $name;
	}

	/**
	 * Returns the name to use for the command line.
	 *
	 * @param string $name
	 * @return string
	 */
	protected function getCommandLineName($name) {
		return strtolower(trim(basename(str_replace('\\', '/', $name))));
	}

	/**
	 * Renders the migration class template.
	 *
	 * @param array $context
	 * @return string
	 */
	protected function getTemplate($context) {
		$path = Path::join(Framework::path('horizon'), 'resources/ace/make/command.twig');
		$view = new Template($path, $context);
        return $view->render();
	}

}
