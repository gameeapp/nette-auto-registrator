# nette-auto-registrator

Using this extension, you don't have to list all classes in neon config. This extension will automatically register all classes for you.

## Installation

```sh
composer require gamee/nette-auto-registrator
```

## Usage

### config.neon:

```
extensions:
	autoRegistrator: Gamee\AutoRegistrator\DI\AutoRegistratorExtension

autoRegistrator:
	skipDirs:
		- Extension
	scanDirs:
		- %appDir%
	skipFilesPatterns:
		- '/Exception\.php$/'
	skipClasses:
		- App\Foo\Bar
		- App\MyBoomCreator
```

### Yes sir, you can use it also in another extension:

```php
declare(strict_types=1);

namespace MyProject\Foo\DI;

use Gamee\AutoRegistrator\DI\AutoRegistratorExtension;
use Nette\DI\CompilerExtension;

final class FooExtension extends CompilerExtension
{

	public function loadConfiguration(): void
	{
		AutoRegistratorExtension::configure(
			$this->compiler,
			[
				'scanDirs' => [__DIR__ . '/..'],
				'skipDirs' => [
					'Enum'
				],
				'skipFilesPatterns' => [
					'/Extension\.php$/',
					'/Event\.php$/',
				],
				'skipClasses' => [],
			]
		);
	}
}

```
