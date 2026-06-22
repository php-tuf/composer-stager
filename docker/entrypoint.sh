#!/bin/sh
set -e

#!/bin/sh
set -e

#!/bin/sh
set -e

# When SYMFONY_MAJOR is set, resolve dependencies fresh via composer update,
# constraining to the specified Symfony major version. The lock file is backed
# up before and restored after so the host working tree stays clean.
if [ -n "${SYMFONY_MAJOR:-}" ]; then
    if [ -f /app/composer.lock ]; then
        cp /app/composer.lock /tmp/composer.lock.bak
    fi

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

    if [ -f /tmp/composer.lock.bak ]; then
        mv /tmp/composer.lock.bak /app/composer.lock
    else
        rm -f /app/composer.lock
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
