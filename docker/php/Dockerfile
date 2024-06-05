FROM php:8.2-cli-alpine
COPY --from=composer/composer /usr/bin/composer /usr/bin/composer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN apk update && apk add zip
RUN install-php-extensions xdebug

