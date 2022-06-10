# Composer Stager

[![Latest Unstable Version](https://poser.pugx.org/php-tuf/composer-stager/v/stable)](https://packagist.org/packages/php-tuf/composer-stager)
[![Tests status](https://github.com/php-tuf/composer-stager/actions/workflows/main.yml/badge.svg)](https://github.com/php-tuf/composer-stager/actions/workflows/main.yml)
[![Coverage](https://img.shields.io/badge/Coverage-100%25-brightgreen.svg?style=flat)](https://github.com/php-tuf/composer-stager/actions/workflows/main.yml)
[![PHPStan](https://img.shields.io/badge/PHPStan-max-brightgreen.svg?style=flat)](https://github.com/phpstan/phpstan)
[![Psalm](https://img.shields.io/badge/Psalm-1-brightgreen.svg?style=flat)](https://github.com/phpstan/phpstan)
[![PHPMD](https://img.shields.io/static/v1?label=PHPMD&message=all&color=brightgreen)](https://phpmd.org/)

Composer Stager makes long-running Composer commands safe to run on a codebase in production by "staging" them--performing them on a non-live copy of the codebase and syncing back the result for the least possible downtime.

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
        $activeDir = PathFactory::create('/var/www/public');
        $stagingDir = PathFactory::create('/var/www/staging');
        $exclusions = new PathList([
            'cache',
            'uploads',
        ]);

        // Copy the codebase to the staging directory.
        $this->beginner->begin($activeDir, $stagingDir, $exclusions);

        // Run a Composer command on it.
        $this->stager->stage([
            'require',
            'example/package',
            '--update-with-all-dependencies',
        ], $stagingDir);

        // Sync the changes back to the active directory.
        $this->committer->commit($stagingDir, $activeDir, $exclusions);

        // Remove the staging directory.
        $this->cleaner->clean($stagingDir);
    }
}
```

## Contributing

[Pull requests](https://github.com/php-tuf/composer-stager/pulls?q=is%3Apr+is%3Aopen+sort%3Aupdated-desc) are welcome. For major changes, please [open an issue](https://github.com/php-tuf/composer-stager/issues?q=is%3Aissue+is%3Aopen+sort%3Aupdated-desc) first to discuss what you would like to change. Observe [the coding standards](https://github.com/php-tuf/composer-stager/wiki/Coding-standards) and make sure to add and update tests as appropriate.

---

[More info in the Wiki.](https://github.com/php-tuf/composer-stager/wiki)
