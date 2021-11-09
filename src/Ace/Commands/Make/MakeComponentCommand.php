<?php

namespace Horizon\Ace\Commands\Make;

use Horizon\Ace\Util\FileGenerator;
use Horizon\Console\Command;
use Horizon\Foundation\Application;
use ReflectionClass;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MakeComponentCommand extends Command {

	/**
	 * Configures the command.
	 */
	protected function configure() {
		$this->setDescription('Makes a new component file');
		$this->addArgument('name', InputArgument::REQUIRED, 'The name of the component.');
		$this->addOption('root', 'r', InputOption::VALUE_NONE, 'Uses the root source directory.');
		$this->addOption('open', 'o', InputOption::VALUE_NONE, 'Opens the file with your default PHP editor.');
		$this->addOption('class', 'c', InputOption::VALUE_OPTIONAL, 'Creates a class for the component.');
	}

	/**
	 * Executes the command.
	 */
	protected function execute(InputInterface $in, OutputInterface $out) {
		$name = $this->getNameGenerator($in);
		$class = $this->getClassGenerator($in);

		$name->renderFile('make/components/component', [
			'componentName' => $name->getClassName(),
			'className' => $class ? $class->getClass() : null
		], $out);

		if (isset($class)) {
			$class->assertClassName(['Component']);
			$class->renderClassFile('make/components/class', [
				'componentName' => $name->getFileInputName(false)
			], $out);
		}

		if ($in->getOption('open')) {
			exec('start ' . $name->resolveFilePath());
		}
	}

	/**
	 * Returns a generator for the component name.
	 *
	 * @param InputInterface $in
	 * @return FileGenerator
	 */
	protected function getNameGenerator(InputInterface $in) {
		$path = new FileGenerator($in->getArgument('name'));
		$path->baseDir = Application::paths()->components();
		$path->extension = 'twig';

		return $path;
	}

	/**
	 * Returns a generator for the preferred class name, or `null` if the user has not requested a class.
	 *
	 * @param InputInterface $in
	 * @return FileGenerator|null
	 */
	protected function getClassGenerator(InputInterface $in) {
		$reflection = new ReflectionClass($in);
		$property = $reflection->getProperty('options');
		$property->setAccessible(true);
		$options = $property->getValue($in);

		if (array_key_exists('class', $options)) {
			$input = $options['class'];
			$name = $input ?: $in->getArgument('name');

			$path = new FileGenerator($name);
			$path->namespace = $in->getOption('root') ? 'App' : 'App/View/Components';
			$path->baseDir = Application::paths()->src($in->getOption('root') ? '' : 'View/Components');
			$path->classNameSuffix = 'Component';

			return $path;
		}
	}

}
