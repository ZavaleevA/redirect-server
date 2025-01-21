# Используем официальный образ PHP с встроенным веб-сервером
FROM php:8.1-cli

# Устанавливаем зависимости (если потребуются)
RUN apt-get update && apt-get install -y \
    curl \
    unzip \
    && docker-php-ext-install pdo_mysql

# Копируем все файлы проекта в контейнер
COPY . /var/www/html

# Указываем рабочую директорию
WORKDIR /var/www/html

# Устанавливаем права на файлы (если необходимо)
RUN chmod -R 755 /var/www/html

# Указываем команду для запуска PHP-сервера
CMD ["php", "-S", "0.0.0.0:80", "-t", "/var/www/html"]
