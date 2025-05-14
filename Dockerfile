FROM php:8.2-apache

# Install PHP ekstensi
RUN apt-get update && apt-get install -y \
    libzip-dev libpng-dev libonig-dev libxml2-dev unzip git \
    && docker-php-ext-install pdo pdo_mysql mbstring zip exif

# Enable mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy project
COPY . .

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

# Symlink + permission
RUN php artisan storage:link \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 755 public storage

# Apache config agar routing Laravel benar
RUN echo '<Directory /var/www/html/public>\n\
    AllowOverride All\n\
</Directory>' > /etc/apache2/conf-available/laravel.conf \
 && a2enconf laravel

# Gunakan direktori public sebagai root
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

# Restart Apache config
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf

EXPOSE 8080
