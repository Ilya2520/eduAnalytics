FROM php:8.2-fpm

# Установка зависимостей
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql zip

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
