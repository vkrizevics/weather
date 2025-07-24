FROM php:8.2-fpm

# Install dependencies including cron, MariaDB 11.8 repo, and phpMyAdmin dependencies
RUN apt-get update && apt-get install -y gnupg wget lsb-release ca-certificates unzip zip git nginx mariadb-server cron libicu-dev libonig-dev libxml2-dev libzip-dev libpq-dev curl && \
    wget -O- https://mariadb.org/mariadb_release_signing_key.asc | gpg --dearmor | tee /etc/apt/trusted.gpg.d/mariadb.gpg > /dev/null && \
    echo "deb [signed-by=/etc/apt/trusted.gpg.d/mariadb.gpg] http://mirror.mariadb.org/repo/11.8/debian $(lsb_release -cs) main" > /etc/apt/sources.list.d/mariadb.list && \
    apt-get update && apt-get install -y mariadb-client

# Install PHP extensions including mysqli (needed for phpMyAdmin), pdo_mysql, intl, zip, opcache
RUN docker-php-ext-install intl pdo pdo_mysql zip opcache mysqli && \
    pecl install pcov && docker-php-ext-enable pcov && \
    echo "pcov.enabled=0\npcov.directory=/var/www/html" > /usr/local/etc/php/conf.d/pcov.ini

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Download and set up phpMyAdmin
RUN mkdir -p /var/www/phpmyadmin && \
    curl -L https://files.phpmyadmin.net/phpMyAdmin/5.2.1/phpMyAdmin-5.2.1-all-languages.tar.gz | tar zx --strip-components=1 -C /var/www/phpmyadmin && \
    chown -R www-data:www-data /var/www/phpmyadmin

# Create phpMyAdmin config.inc.php with TCP connection settings
RUN printf '<?php\n\
$cfg["blowfish_secret"] = "a_very_secret_random_string"; // change this\n\
$i = 0;\n\
$i++;\n\
$cfg["Servers"][$i]["host"] = "127.0.0.1";\n\
$cfg["Servers"][$i]["port"] = "3306";\n\
$cfg["Servers"][$i]["auth_type"] = "cookie";\n\
$cfg["Servers"][$i]["user"] = "symfony";\n\
$cfg["Servers"][$i]["password"] = "symfony";\n\
$cfg["UploadDir"] = "";\n\
$cfg["SaveDir"] = "";\n' > /var/www/phpmyadmin/config.inc.php


# Create required MariaDB directories
RUN mkdir -p /var/lib/mysql /var/run/mysqld && chown -R mysql:mysql /var/lib/mysql /var/run/mysqld

# Set working directory
WORKDIR /var/www/html

# Copy project files and Nginx config
COPY . .

# Setup your DATABASE_URL environment variables
RUN echo 'DATABASE_URL="mysql://symfony:symfony@127.0.0.1:3306/symfony?serverVersion=mariadb-11.8.0"' > .env.local
RUN echo 'DATABASE_URL="mysql://symfony:symfony@127.0.0.1:3306/symfony_test?serverVersion=mariadb-11.8.0"' > .env.test.local
RUN echo 'DATABASE_URL="mysql://symfony:symfony@127.0.0.1:3306/symfony?serverVersion=mariadb-11.8.0"' > .env.dev.local

# Set permissions for www-data user
RUN chown -R www-data:www-data /var/www/html /var/www/phpmyadmin

# Set Composer cache directory environment variable
ENV COMPOSER_CACHE_DIR=/tmp/composer-cache

# Switch to www-data user to install PHP dependencies
USER www-data

RUN mkdir -p $COMPOSER_CACHE_DIR && \
    composer install --no-interaction --no-scripts --prefer-dist --optimize-autoloader

# Switch back to root user for nginx and permissions setup
USER root

# Setup Nginx config for your app + phpMyAdmin
COPY default.conf /etc/nginx/sites-available/default
RUN ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

# Setup cron job for syncing stations every hour
RUN echo "0 * * * * /usr/local/bin/php /var/www/html/bin/console app:sync-stations >> /var/log/cron.log 2>&1" > /etc/cron.d/sync-stations && \
    chmod 0644 /etc/cron.d/sync-stations && \
    crontab -u www-data /etc/cron.d/sync-stations && \
    touch /var/log/cron.log && chmod 666 /var/log/cron.log

# Symfony and MariaDB initialization script
RUN chmod +x ./bin/console && \
    echo '#!/bin/bash\n\
mariadbd-safe --datadir=/var/lib/mysql &\n\
sleep 5\n\
mysql -uroot -e "CREATE DATABASE IF NOT EXISTS symfony;"\n\
mysql -uroot -e "CREATE USER IF NOT EXISTS '\''symfony'\''@'\''localhost'\'' IDENTIFIED BY '\''symfony'\'';"\n\
mysql -uroot -e "GRANT ALL PRIVILEGES ON symfony.* TO '\''symfony'\''@'\''localhost'\'';"\n\
mysql -uroot -e "CREATE DATABASE IF NOT EXISTS symfony_test;"\n\
mysql -uroot -e "GRANT ALL PRIVILEGES ON symfony_test.* TO '\''symfony'\''@'\''localhost'\'';"\n\
php bin/console doctrine:migrations:migrate --no-interaction --env=dev\n\
php bin/console doctrine:migrations:migrate --no-interaction --env=test\n\
mysqladmin -uroot shutdown' > /init.sh && \
    chmod +x /init.sh && /init.sh

# Entrypoint script to run MariaDB, cron, PHP-FPM, and Nginx
RUN echo '#!/bin/bash\n\
mariadbd-safe --datadir=/var/lib/mysql &\n\
cron\n\
php-fpm -D\n\
nginx -g "daemon off;"' > /start.sh && chmod +x /start.sh

# Expose HTTP port
EXPOSE 80

# Start the services
CMD ["/start.sh"]
