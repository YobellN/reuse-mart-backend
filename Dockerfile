FROM dunglas/frankenphp:php8.3

ENV SERVER_NAME=":80"

WORKDIR /app

# Copy source Laravel ke /app
COPY . /app

# Copy SSL certificate used by the database client.
COPY ./storage/app/private/DigiCertGlobalRootCA.crt.pem /app/storage/app/private/DigiCertGlobalRootCA.crt.pem

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

CMD ["frankenphp", "--document-root=public", "--worker=/app/public/index.php"]
