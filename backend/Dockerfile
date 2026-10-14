# ---- Tahap 1: install dependensi PHP ----
FROM composer:2.8 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --no-dev --ignore-platform-reqs

# ---- Tahap 2: image runtime ----
FROM php:8.3-cli-alpine
RUN docker-php-ext-install pdo_mysql
WORKDIR /var/www/html
COPY --from=vendor /app ./
RUN chown -R www-data:www-data storage bootstrap/cache
USER www-data
EXPOSE 8000
CMD sh -c 'php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8000'
