# Build deps
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress
# App code
COPY public ./public

# Runtime
FROM php:8.3-apache
RUN docker-php-ext-install sockets
WORKDIR /var/www/html
COPY --from=vendor /app /var/www/html
EXPOSE 80
