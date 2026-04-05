FROM php:8.2-cli

WORKDIR /app

RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libxml2-dev libzip-dev libcurl4-openssl-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring xml bcmath gd zip curl fileinfo \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install dependencies (cached layer)
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy app code
COPY . .

RUN composer dump-autoload --optimize \
    && mkdir -p storage/logs storage/framework/{cache,sessions,views} bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && php artisan storage:link 2>/dev/null || true

EXPOSE 8001

CMD php artisan migrate --force 2>/dev/null; php artisan serve --host=0.0.0.0 --port=${PORT:-8001}
