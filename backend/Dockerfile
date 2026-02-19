FROM php:8.5-fpm

RUN apt-get update \
    && apt-get install -y \
    wget zip unzip \
    libzip-dev \
    libpq-dev \
    && docker-php-ext-install pgsql pdo_pgsql pdo \
    && docker-php-ext-enable pgsql pdo_pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy and install dependencies at build time
COPY . .
RUN cp .env.dev .env
RUN composer install --no-interaction --optimize-autoloader
RUN php artisan config:clear

# Copy and setup startup script
COPY start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

CMD ["/usr/local/bin/start.sh"]
