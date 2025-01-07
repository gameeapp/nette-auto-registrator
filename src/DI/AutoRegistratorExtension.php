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
    private array $defaults = [
        'scanDirs' => [],
        'skipDirs' => [],
        'skipFilesPatterns' => [],
        'skipClasses' => [],
        'skipSubclassesOf' => [
            \Throwable::class,
        ],
    ];


    public static function configure(
        Compiler $compiler,
        array $config,
    ): void
    {
        $extension = new self;
        $extension->setCompiler($compiler, 'autoRegistrator');
        $extension->setConfig($config);
        $extension->loadConfiguration();
    }


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
                $handle = \fopen((string) $file->getRealPath(), 'rb');

                if ($handle === false) {
                    continue;
                }

                $namespace = null;
                $className = null;

                while ($line = \fgets($handle)) {
                    if (str_starts_with($line, 'namespace ')) {
                        $namespace = \substr($line, 10, -2);
                    } else {
                        $classPosition = \strpos($line, 'class ');

                        if ($classPosition !== false) {
                            if (str_contains($line, 'abstract ')) {
                                break;
                            }

                            $className = \substr(
                                $line,
                                (6 + $classPosition),
                                \strlen($line) - (6 + $classPosition + 1),
                            );
                        }
                    }

                    if (($line[0] ?? '') === '{') {
                        break;
                    }
                }

                \fclose($handle);

                if ($className === null || $namespace === null) {
                    continue;
                }

                $className = \explode(' ', $className)[0];

                $fullClassName = $namespace . '\\' . $className;

                if (\in_array($fullClassName, $this->config['skipClasses'], true)) {
                    continue;
                }

                foreach ($this->config['skipSubclassesOf'] as $subclassOf) {
                    if (\is_subclass_of($fullClassName, $subclassOf)) {
                        continue 2;
                    }
                }

                $this->getContainerBuilder()->addDefinition($this->prefix(\lcfirst($className)))
                    ->setFactory($fullClassName);
            }
        }
    }
}
