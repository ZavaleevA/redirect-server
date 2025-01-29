# Use the official PHP image with a built-in web server
FROM php:8.1-cli

# Install dependencies, including Composer
RUN apt-get update && apt-get install -y \
    curl \
    unzip \
    git \
    && curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer \
    && docker-php-ext-install pdo_mysql

# Copy all project files into the container
COPY . /var/www/html

# Set the working directory
WORKDIR /var/www/html

# Install dependencies via Composer
RUN composer install

# Set file permissions (if necessary)
RUN chmod -R 755 /var/www/html

# Specify the command to start the PHP server
CMD ["php", "-S", "0.0.0.0:80", "-t", "/var/www/html"]
