# Composer Stager

[![Tests status](https://github.com/php-tuf/composer-stager/actions/workflows/main.yml/badge.svg)](https://github.com/php-tuf/composer-stager/actions/workflows/main.yml)
[![PHPStan](https://img.shields.io/badge/PHPStan-max-brightgreen.svg?style=flat)](https://github.com/phpstan/phpstan)

Composer Stager makes long-running Composer commands safe to run on a codebase in production by "staging" them--performing them on a non-live copy of the codebase and syncing back the result for the least possible downtime.

## Contributing

Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

Please make sure to update tests as appropriate.

## Documentation

* [Project glossary](glossary.md)
