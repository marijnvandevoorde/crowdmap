FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    unzip \
    && docker-php-ext-install pdo pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock* ./
RUN composer install --no-dev --optimize-autoloader

COPY . .

# Create entrypoint script that runs migrations then starts php-fpm
RUN echo '#!/bin/sh\nphp bin/migrate up\nexec php-fpm' > /entrypoint.sh && chmod +x /entrypoint.sh

EXPOSE 9000

CMD ["/entrypoint.sh"]
