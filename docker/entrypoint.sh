#!/bin/sh
set -e

# When SYMFONY_MAJOR is set, resolve dependencies fresh via composer update,
# constraining to the specified Symfony major version. Otherwise use composer
# install with the lock file.
if [ -n "${SYMFONY_MAJOR:-}" ]; then
    if [ "${DEPENDENCIES:-high}" = "low" ]; then
        composer update \
            --prefer-lowest \
            --prefer-stable \
            --no-interaction \
            --no-progress \
            --quiet \
            --with "symfony/filesystem:^${SYMFONY_MAJOR}" \
            --with "symfony/process:^${SYMFONY_MAJOR}"
    else
        composer update \
            --no-interaction \
            --no-progress \
            --quiet \
            --with "symfony/filesystem:^${SYMFONY_MAJOR}" \
            --with "symfony/process:^${SYMFONY_MAJOR}"
    fi
else
    composer install --no-interaction --no-progress --quiet
fi

# Print PHP version header
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
php --version | head -1
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

exec "$@"
