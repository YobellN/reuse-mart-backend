FROM php:8.3-cli

WORKDIR /app

COPY --chown=www-data:www-data . /app

RUN apt update && apt install -y \
    zip libzip-dev \
    libonig-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    libssl-dev \
    && docker-php-ext-install \
    zip \
    pcntl \
    curl \
    mbstring \
    pdo \
    pdo_mysql \
    xml \
    bcmath \
    && docker-php-ext-enable \
    zip \
    curl \
    mbstring \
    pdo_mysql \
    xml \
    bcmath


COPY --from=composer:2.2 /usr/bin/composer /usr/bin/composer

RUN composer install && \
    composer require laravel/octane && \
    php artisan octane:install --server=frankenphp 

EXPOSE 8000

CMD php artisan octane:start --server=frankenphp --host=0.0.0.0 --port=8000
