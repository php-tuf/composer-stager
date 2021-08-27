# Composer Stager

[![Latest Unstable Version](https://poser.pugx.org/php-tuf/composer-stager/v/unstable)](https://packagist.org/packages/php-tuf/composer-stager)
[![Tests status](https://github.com/php-tuf/composer-stager/actions/workflows/main.yml/badge.svg)](https://github.com/php-tuf/composer-stager/actions/workflows/main.yml)
[![PHPStan](https://img.shields.io/badge/PHPStan-max-brightgreen.svg?style=flat)](https://github.com/phpstan/phpstan)
[![Psalm](https://img.shields.io/badge/Psalm-1-brightgreen.svg?style=flat)](https://github.com/phpstan/phpstan)
[![PHPMD](https://img.shields.io/static/v1?label=PHPMD&message=all&color=brightgreen)](https://phpmd.org/)

Composer Stager makes long-running Composer commands safe to run on a codebase in production by "staging" them--performing them on a non-live copy of the codebase and syncing back the result for the least possible downtime. It can be used via its [console application](#console-application) or as a [Composer library](#composer-library):

## Warning!

Composer Stager is in the very early stages of development and _highly_ unstable. Unless you are part of the PHP-TUF development team, do not use it.

## Console application

The Console application can be used stand-alone by installing it via Git and invoking its executable:

```shell
$ git clone https://github.com/php-tuf/composer-stager.git
$ php composer-stager/bin/composer-stager
```

### Available commands

* `begin` - Begins the staging process by copying the active directory to the staging directory.
* `stage` - Executes a Composer command in the staging directory.
* `commit` - Makes the staged changes live by syncing the active directory with the staging directory.
* `clean` - Removes the staging directory.

### Example workflow:

```shell
# Copy the codebase to the staging directory.
$ composer-stager begin

# Run a Composer command on it.
$ composer-stager stage -- require lorem/ipsum --update-with-all-dependencies

# Sync the changes back to the active directory.
$ composer-stager commit --no-interaction

# Remove the staging directory.
$ composer-stager clean --no-interaction
```

## Composer library

The Composer library is installed, of course, via Composer:

<!-- @todo Remove the custom repository command once we are publishing the library to Packagist. -->
```shell
composer require php-tuf/composer-stager
```

It is invoked via its PHP API. Given a configured service container that supports autowiring (e.g., [Symfony's](https://symfony.com/doc/current/service_container.html)) its services can be used like the following, for example:

```php
class Updater
{
    public function __construct(
        BeginnerInterface $beginner,
        StagerInterface $stager,
        CommitterInterface $committer,
        CleanerInterface $cleaner
    )
    {
        $this->beginner = $beginner;
        $this->stager = $stager;
        $this->committer = $committer;
        $this->cleaner = $cleaner;
    }

    public function update(): void
    {
        $activeDir = '/var/www/public';
        $stagingDir = '/var/www/staging';

        // Copy the codebase to the staging directory.
        $this->beginner->begin($activeDir, $stagingDir);

        // Run a Composer command on it.
        $this->stager->stage([
            'require',
            'lorem/ipsum',
            '--update-with-all-dependencies',
        ], $stagingDir);

        // Sync the changes back to the active directory.
        $this->committer->commit($stagingDir, $activeDir);

        // Remove the staging directory.
        $this->cleaner->clean($stagingDir);
    }
}
```

## Contributing

Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

Please make sure to update tests as appropriate.

## Documentation

* [Project glossary](glossary.md)
