FROM dunglas/frankenphp:php8.3

ENV SERVER_NAME=":80"

WORKDIR /app

COPY . /app

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


COPY --from=composer:2.2 /usr/bin/composer /usr/bin/composer

RUN composer install
