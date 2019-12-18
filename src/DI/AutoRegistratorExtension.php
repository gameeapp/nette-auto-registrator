<?php

declare(strict_types=1);

namespace Gamee\AutoRegistrator\DI;

use Nette\DI\Compiler;
use Nette\DI\CompilerExtension;
use Symfony\Component\Finder\Finder;

/**
 * @property-read array $config
 */
final class AutoRegistratorExtension extends CompilerExtension
{

	/**
	 * @var array
	 */
	private $defaults = [
		'scanDirs' => [],
		'skipDirs' => [
			'Exception',
		],
		'skipFilesPatterns' => [],
		'skipClasses' => [],
	];


	public function loadConfiguration(): void
	{
		$this->validateConfig($this->defaults);

		foreach ($this->config['scanDirs'] as $dir) {
			$files = (new Finder)
				->files()
				->name('/[A-Z][a-zA-Z0-9]+\.php$/')
				->notName($this->config['skipFilesPatterns'])
				->exclude($this->config['skipDirs'])
				->in($dir);

			foreach ($files as $file) {
				$handle = fopen((string) $file->getRealPath(), 'r');

				if ($handle === false) {
					continue;
				}

				$namespace = null;
				$className = null;

				while ($line = fgets($handle)) {
					if (strpos($line, 'namespace ') === 0) {
						$namespace = substr($line, 10, strlen($line) - 12);
					} else {
						$classPosition = strpos($line, 'class ');

						if ($classPosition !== false) {
							if (strpos($line, 'abstract class') !== false) {
								break;
							}

							$className = substr(
								$line,
								(6 + $classPosition),
								strlen($line) - (6 + $classPosition + 1)
							);
						}
					}

					if (($line[0] ?? '') === '{') {
						break;
					}
				}

				fclose($handle);

				if ($className === null || $namespace === null) {
					continue;
				}

				$className = explode(' ', $className)[0];

				$fullClassName = $namespace . '\\' . $className;

				if (in_array($fullClassName, $this->config['skipClasses'], true)) {
					continue;
				}

				$this->getContainerBuilder()->addDefinition($this->prefix(lcfirst($className)))
					->setFactory($fullClassName);
			}
		}
	}


	public static function configure(
		Compiler $compiler,
		array $config
	): void
	{
		$extension = new static;
		$extension->setCompiler($compiler, 'autoRegistrator');
		$extension->setConfig($config);
		$extension->loadConfiguration();
	}
}
