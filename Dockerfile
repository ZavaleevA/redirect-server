# Используем официальный образ PHP с встроенным веб-сервером
FROM php:8.1-cli

# Устанавливаем зависимости, включая Composer
RUN apt-get update && apt-get install -y \
    curl \
    unzip \
    git \
    && curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer \
    && docker-php-ext-install pdo_mysql

# Копируем все файлы проекта в контейнер
COPY . /var/www/html

# Указываем рабочую директорию
WORKDIR /var/www/html

# Устанавливаем зависимости через Composer
RUN composer install

# Устанавливаем права на файлы (если необходимо)
RUN chmod -R 755 /var/www/html

# Указываем команду для запуска PHP-сервера
CMD ["php", "-S", "0.0.0.0:80", "-t", "/var/www/html"]
