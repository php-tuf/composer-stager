# Releasing a New Version

For project maintainers.

1. Before committing to a release...
    1. [Check the issue queue](https://github.com/php-tuf/composer-stager/issues) for critical issues.
    1. Search the codebase for important `@todo` comments.
1. Run automated tests with `composer all`.
1. Create a release tag:
    1. Choose a [semantic version](https://semver.org/) number (`X.Y.Z`).
    1. Set `\PhpTuf\ComposerStager\Console\Application::VERSION` the version to `X.Y.Z` and commit.
    1. Create a tag named `vX.Y.Z`..
    1. Return `\PhpTuf\ComposerStager\Console\Application::VERSION` to dev, i.e., `X.Y.Z-dev` and commit.
    1. Push the release tag to GitHub along with the updated `main` branch.
1. [Create a GitHub release.](https://help.github.com/articles/creating-releases/)
    1. Set the tag version and release title both to the new version number.
