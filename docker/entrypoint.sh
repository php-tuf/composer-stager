#!/bin/sh
set -e

#!/bin/sh
set -e

# When SYMFONY_MAJOR is set, resolve dependencies fresh via composer update,
# constraining to the specified Symfony major version. We work from a temp
# copy of composer.json so that composer.lock on the host is never modified,
# while vendor is still installed into the mounted volume.
if [ -n "${SYMFONY_MAJOR:-}" ]; then
    tmpdir=$(mktemp -d)
    cp /app/composer.json "$tmpdir/composer.json"
    # Redirect vendor installation back to the mounted volume.
    composer config --working-dir="$tmpdir" vendor-dir /app/vendor
    if [ "${DEPENDENCIES:-high}" = "low" ]; then
        composer update \
            --working-dir="$tmpdir" \
            --prefer-lowest \
            --prefer-stable \
            --no-interaction \
            --no-progress \
            --quiet \
            --with "symfony/filesystem:^${SYMFONY_MAJOR}" \
            --with "symfony/process:^${SYMFONY_MAJOR}"
    else
        composer update \
            --working-dir="$tmpdir" \
            --no-interaction \
            --no-progress \
            --quiet \
            --with "symfony/filesystem:^${SYMFONY_MAJOR}" \
            --with "symfony/process:^${SYMFONY_MAJOR}"
    fi
    rm -rf "$tmpdir"
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
