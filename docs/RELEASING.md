# Releasing a New Version

For project maintainers.

1. Before committing to a release...
    1. [Check the issue queue](https://github.com/php-tuf/composer-stager/issues) for critical issues.
    1. Search the codebase for important `@todo` comments.
1. Run automated tests with `composer all`.
1. Create a release tag with [Gitflow](https://github.com/nvie/gitflow):
    1. Choose a [semantic version](https://semver.org/) number (`x.y.z`).
    1. Start the release with `git flow release start x.y.z`.
    1. Finish the release with `git flow release finish x.y.z`.
    1. Push the release tag to GitHub along with the updated `develop` and `main` branches.
1. [Create a GitHub release.](https://help.github.com/articles/creating-releases/)
    1. Set the tag version and release title both to the new version number.
1. Update the package at https://packagist.org/packages/php-tuf/composer-stager to publish the new release. (Delete any extraneous versions it creates, e.g., `dev-wip`.)
