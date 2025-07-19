FROM php:8.2-fpm

# Install dependencies and MariaDB 11.8 repo
RUN apt-get update && apt-get install -y gnupg wget lsb-release ca-certificates && \
    wget -O- https://mariadb.org/mariadb_release_signing_key.asc | gpg --dearmor | tee /etc/apt/trusted.gpg.d/mariadb.gpg > /dev/null && \
    echo "deb [signed-by=/etc/apt/trusted.gpg.d/mariadb.gpg] http://mirror.mariadb.org/repo/11.8/debian $(lsb_release -cs) main" > /etc/apt/sources.list.d/mariadb.list && \
    apt-get update && apt-get install -y \
    git unzip zip nginx mariadb-server wget \
    libicu-dev libonig-dev libxml2-dev libzip-dev libpq-dev \
    && docker-php-ext-install intl pdo pdo_mysql zip opcache

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Create required MariaDB directories
RUN mkdir -p /var/lib/mysql /var/run/mysqld && \
    chown -R mysql:mysql /var/lib/mysql /var/run/mysqld

# Set working directory
WORKDIR /var/www/html

# Copy project files and Nginx config
COPY . .

# Create writable Symfony directories with correct permissions
RUN mkdir -p var/cache var/log && chown -R www-data:www-data var

# Warm up the cache using www-data
USER www-data
RUN php bin/console cache:warmup
USER root

COPY default.conf /etc/nginx/sites-available/default
RUN ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

# Symfony and MariaDB setup script
RUN chmod +x ./bin/console && \
    echo '#!/bin/bash\n\
mariadbd-safe --datadir=/var/lib/mysql &\n\
sleep 5\n\
mysql -uroot -e "CREATE DATABASE IF NOT EXISTS symfony;"\n\
mysql -uroot -e "CREATE USER IF NOT EXISTS '\''symfony'\''@'\''localhost'\'' IDENTIFIED BY '\''symfony'\'';"\n\
mysql -uroot -e "GRANT ALL PRIVILEGES ON symfony.* TO '\''symfony'\''@'\''localhost'\'';"\n\
composer install --no-interaction --no-scripts\n\
composer require --dev phpunit/phpunit ^9.5 --no-interaction\n\
php bin/console doctrine:migrations:migrate --no-interaction\n\
mysqladmin -uroot shutdown' > /init.sh && \
    chmod +x /init.sh && /init.sh

# Entrypoint script to run services
RUN echo '#!/bin/bash\n\
mariadbd-safe --datadir=/var/lib/mysql &\n\
php-fpm -D\n\
nginx -g "daemon off;"' > /start.sh && chmod +x /start.sh

# Expose HTTP port
EXPOSE 80

# Start services
CMD ["/start.sh"]
