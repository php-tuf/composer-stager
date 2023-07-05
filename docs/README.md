
# Composer Stager

[![Latest stable version](https://poser.pugx.org/php-tuf/composer-stager/v/stable)](https://packagist.org/packages/php-tuf/composer-stager)
[![Tests status](https://github.com/php-tuf/composer-stager/actions/workflows/main.yml/badge.svg?branch=main)](https://github.com/php-tuf/composer-stager/actions/workflows/main.yml)
[![Coverage](https://img.shields.io/badge/Coverage-100%25-brightgreen.svg?style=flat)](https://github.com/php-tuf/composer-stager/actions/workflows/main.yml) <!-- A static "100%" value can be used safely here because grumphp will fail builds if coverage falls below that. See grumphp.yml.dist. -->
[![PHPStan](https://img.shields.io/badge/PHPStan-max-brightgreen.svg?style=flat)](https://github.com/phpstan/phpstan)
[![Psalm](https://img.shields.io/badge/Psalm-1-brightgreen.svg?style=flat)](https://psalm.dev/)
[![PHPMD](https://img.shields.io/static/v1?label=PHPMD&message=all&color=brightgreen)](https://phpmd.org/)

Composer Stager makes long-running Composer commands safe to run on a codebase in production by "staging" them--performing them on a non-live copy of the codebase and syncing back the result for the least possible downtime.

- [Installation](#installation)
- [Usage](#usage)
- [Configuring services](#configuring-services)
- [Example](#example)
- [Contributing](#contributing)

## Installation

The library is installed via Composer:

```shell
composer require php-tuf/composer-stager
```

## Usage

It is invoked via its PHP API. Given a configured service container ([see below](#configuring-services)), its services can be used like the following, for example:

```php
class Updater
{
    public function __construct(
        private readonly BeginnerInterface $beginner,
        private readonly StagerInterface $stager,
        private readonly CommitterInterface $committer,
        private readonly CleanerInterface $cleaner,
    ) {
    }

    public function update(): void
    {
        $activeDir = PathFactory::create('/var/www/public');
        $stagingDir = PathFactory::create('/var/www/staging');
        $exclusions = new PathList(
            'cache',
            'uploads',
        );

        // Copy the codebase to the staging directory.
        $this->beginner->begin($activeDir, $stagingDir, $exclusions);

        // Run a Composer command on it.
        $this->stager->stage([
            'require',
            'example/package',
            '--update-with-all-dependencies',
        ], $activeDir, $stagingDir);

        // Sync the changes back to the active directory.
        $this->committer->commit($stagingDir, $activeDir, $exclusions);

        // Remove the staging directory.
        $this->cleaner->clean($stagingDir);
    }
}
```

## Configuring services

Composer Stager uses the [dependency injection](https://en.wikipedia.org/wiki/Dependency_injection) pattern, and its services are best accessed via a container that supports autowiring, e.g., [Symfony's](https://symfony.com/doc/current/service_container.html). A basic implementation could look something like this:

```yaml
services:

    _defaults:
        autoconfigure: true
        autowire: true
        public: false

    PhpTuf\ComposerStager\:
        resource: '../vendor/php-tuf/composer-stager/src/*'
        public: true
        exclude:
            - '../vendor/php-tuf/composer-stager/src/*/*/Value'
            - '../vendor/php-tuf/composer-stager/src/API/Exception'

    PhpTuf\ComposerStager\Internal\FileSyncer\Factory\FileSyncerFactory:
        arguments:
            $phpFileSyncer: '@PhpTuf\ComposerStager\API\FileSyncer\Service\PhpFileSyncerInterface'
            $rsyncFileSyncer: '@PhpTuf\ComposerStager\API\FileSyncer\Service\RsyncFileSyncerInterface'
    PhpTuf\ComposerStager\API\FileSyncer\Service\FileSyncerInterface:
        factory: [ '@PhpTuf\ComposerStager\API\FileSyncer\Factory\FileSyncerFactoryInterface', 'create' ]

    Symfony\Component\Filesystem\Filesystem: ~
    Symfony\Component\Process\ExecutableFinder: ~
```

## Example

A complete, functioning example implementation of Composer Stager can be found in the [Composer Stager Console](https://github.com/php-tuf/composer-stager-console) repository.

## Contributing

[Pull requests](https://github.com/php-tuf/composer-stager/pulls?q=is%3Apr+is%3Aopen+sort%3Aupdated-desc) are welcome. For major changes, please [open an issue](https://github.com/php-tuf/composer-stager/issues?q=is%3Aissue+is%3Aopen+sort%3Aupdated-desc) first to discuss what you would like to change. Observe [the coding standards](https://github.com/php-tuf/composer-stager/wiki/Coding-standards-&-style-guide), and if you're able, add and update tests as appropriate.

---

[More info in the Wiki.](https://github.com/php-tuf/composer-stager/wiki)
