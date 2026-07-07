ARG PHP_VERSION=8.3
FROM php:${PHP_VERSION}-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends git rsync unzip \
    && rm -rf /var/lib/apt/lists/*

ARG INSTALL_XDEBUG=false
RUN if [ "$INSTALL_XDEBUG" = "true" ]; then \
    pecl install xdebug && docker-php-ext-enable xdebug; \
fi

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

WORKDIR /app

ENTRYPOINT ["/entrypoint.sh"]
CMD ["php", "./vendor/bin/phpunit"]
