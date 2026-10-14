# BONUS (soal 7): untuk memakai ini, jalankan di folder backend:
#   composer require laravel/octane && php artisan octane:install --server=frankenphp
# lalu ganti nama file ini menjadi Dockerfile (timpa yang lama).

# ---- Tahap 1: install dependensi PHP ----
FROM composer:2.8 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --no-dev --ignore-platform-reqs

# ---- Tahap 2: FrankenPHP ----
FROM dunglas/frankenphp:1-php8.3-alpine
RUN install-php-extensions pcntl pdo_mysql
WORKDIR /app
COPY --from=vendor /app ./
EXPOSE 8000
CMD sh -c 'php artisan migrate --force && php artisan octane:start --server=frankenphp --host=0.0.0.0 --port=8000 --admin-port=2019'
