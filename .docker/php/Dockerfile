FROM php:8.4-fpm

# Switch to root for installation
USER root

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git zip unzip curl wget gnupg2 ca-certificates apt-transport-https \
    libpng-dev libjpeg-dev libonig-dev libxml2-dev libzip-dev \
    libpq-dev libmemcached-dev libmagickwand-dev \
    libssl-dev pkg-config libcurl4-openssl-dev zlib1g-dev \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-install pdo pdo_mysql zip gd intl opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Symfony CLI
RUN curl -sS https://get.symfony.com/cli/installer | bash && \
    mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

# Optional: Add non-root dev user
RUN useradd -ms /bin/bash symfony
USER symfony

WORKDIR /var/www
