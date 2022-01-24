# Composer Stager

[![Latest Unstable Version](https://poser.pugx.org/php-tuf/composer-stager/v/unstable)](https://packagist.org/packages/php-tuf/composer-stager)
[![Tests status](https://github.com/php-tuf/composer-stager/actions/workflows/main.yml/badge.svg)](https://github.com/php-tuf/composer-stager/actions/workflows/main.yml)
[![Coverage](https://img.shields.io/badge/Coverage-100%25-brightgreen.svg?style=flat)](https://github.com/php-tuf/composer-stager/actions/workflows/main.yml)
[![PHPStan](https://img.shields.io/badge/PHPStan-max-brightgreen.svg?style=flat)](https://github.com/phpstan/phpstan)
[![Psalm](https://img.shields.io/badge/Psalm-1-brightgreen.svg?style=flat)](https://github.com/phpstan/phpstan)
[![PHPMD](https://img.shields.io/static/v1?label=PHPMD&message=all&color=brightgreen)](https://phpmd.org/)

Composer Stager makes long-running Composer commands safe to run on a codebase in production by "staging" them--performing them on a non-live copy of the codebase and syncing back the result for the least possible downtime.

<!-- @todo Remove warning once there's a stable release. -->
## Warning!

Composer Stager is in the very early stages of development and _highly_ unstable. Unless you are part of the PHP-TUF development team, do not use it.

## Composer library

The Composer library is installed, of course, via Composer:

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

## Known issues

See the current list of [known issues](known_issues.md).

## Contributing

Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

Please make sure to update tests as appropriate.

## Documentation

* [Project glossary](glossary.md)
