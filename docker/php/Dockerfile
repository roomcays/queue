FROM php:8.2-cli-alpine
COPY --from=composer/composer /usr/bin/composer /usr/bin/composer
RUN apk update && apk add zip
