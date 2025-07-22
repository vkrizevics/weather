FROM php:8.2-fpm

# Install dependencies including cron and MariaDB 11.8 repo
RUN apt-get update && apt-get install -y gnupg wget lsb-release ca-certificates && \
    wget -O- https://mariadb.org/mariadb_release_signing_key.asc | gpg --dearmor | tee /etc/apt/trusted.gpg.d/mariadb.gpg > /dev/null && \
    echo "deb [signed-by=/etc/apt/trusted.gpg.d/mariadb.gpg] http://mirror.mariadb.org/repo/11.8/debian $(lsb_release -cs) main" > /etc/apt/sources.list.d/mariadb.list && \
    apt-get update && apt-get install -y \
    git unzip zip nginx mariadb-server wget cron \
    libicu-dev libonig-dev libxml2-dev libzip-dev libpq-dev \
    && docker-php-ext-install intl pdo pdo_mysql zip opcache \
    && pecl install pcov \
    && docker-php-ext-enable pcov \
    && echo "pcov.enabled=0\npcov.directory=/var/www/html" > /usr/local/etc/php/conf.d/pcov.ini

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Create required MariaDB directories
RUN mkdir -p /var/lib/mysql /var/run/mysqld && \
    chown -R mysql:mysql /var/lib/mysql /var/run/mysqld

# Set working directory
WORKDIR /var/www/html

# Copy project files and Nginx config
COPY . .

RUN chown -R www-data:www-data /var/www/html

# Set permissions and environment so Composer cache directory is writable
ENV COMPOSER_CACHE_DIR=/tmp/composer-cache

# Switch to www-data and install dependencies
USER www-data

RUN mkdir -p $COMPOSER_CACHE_DIR && \
    composer install --no-interaction --no-scripts --prefer-dist --optimize-autoloader

# Back to root user for nginx and permissions
USER root

COPY default.conf /etc/nginx/sites-available/default
RUN ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

# Set up cron job for syncing stations every minute
RUN echo "0 * * * * /usr/local/bin/php /var/www/html/bin/console app:sync-stations >> /var/log/cron.log 2>&1" > /etc/cron.d/sync-stations && \
    chmod 0644 /etc/cron.d/sync-stations && \
    crontab -u www-data /etc/cron.d/sync-stations && \
    touch /var/log/cron.log && \
    chmod 666 /var/log/cron.log

# Symfony and MariaDB setup script
RUN chmod +x ./bin/console && \
    echo '#!/bin/bash\n\
mariadbd-safe --datadir=/var/lib/mysql &\n\
sleep 5\n\
mysql -uroot -e "CREATE DATABASE IF NOT EXISTS symfony;"\n\
mysql -uroot -e "CREATE USER IF NOT EXISTS '\''symfony'\''@'\''localhost'\'' IDENTIFIED BY '\''symfony'\'';"\n\
mysql -uroot -e "GRANT ALL PRIVILEGES ON symfony.* TO '\''symfony'\''@'\''localhost'\'';"\n\
php bin/console doctrine:migrations:migrate --no-interaction\n\
mysqladmin -uroot shutdown' > /init.sh && \
    chmod +x /init.sh && /init.sh

# Entrypoint script to run all services including cron
RUN echo '#!/bin/bash\n\
mariadbd-safe --datadir=/var/lib/mysql &\n\
cron\n\
php-fpm -D\n\
nginx -g "daemon off;"' > /start.sh && chmod +x /start.sh

# Expose HTTP port
EXPOSE 80

# Start services
CMD ["/start.sh"]
