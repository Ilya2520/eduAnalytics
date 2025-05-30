FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    librabbitmq-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    zip \
    sockets \
    && pecl install \
    amqp \
    redis \
    && docker-php-ext-enable \
    amqp \
    redis

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Настройка рабочей директории
WORKDIR /var/www

# Копирование кода
COPY . /var/www

# Установка зависимостей
RUN composer install --optimize-autoloader

# Настройка прав
RUN chown -R www-data:www-data /var/www

CMD ["php-fpm"]