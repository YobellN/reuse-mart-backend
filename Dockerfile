FROM dunglas/frankenphp:php8.3

ENV SERVER_NAME=":80"

WORKDIR /app

# Copy source Laravel ke /app
COPY . /app

# Copy file SSL cert dan firebase key (harus sebelum composer install)
COPY ./storage/app/private/DigiCertGlobalRootCA.crt.pem /app/storage/app/private/DigiCertGlobalRootCA.crt.pem
COPY ./storage/app/firebase-service-account.json /app/storage/app/firebase-service-account.json

# Install dependencies OS + PHP extensions
RUN apt update && apt install -y \
    zip libzip-dev \
    libonig-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    libssl-dev \
    && docker-php-ext-install \
        zip \
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

# Tambahkan composer
COPY --from=composer:2.2 /usr/bin/composer /usr/bin/composer

# Install dependencies Laravel
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set permission (important)
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Storage symlink
RUN php artisan storage:link
