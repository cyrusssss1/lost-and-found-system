FROM php:8.2-apache

RUN docker-php-ext-install mysqli

RUN a2enmod rewrite

COPY . /var/www/html/

WORKDIR /var/www/html/

RUN if [ -f composer.json ]; then \
        apt-get update && \
        apt-get install -y git unzip libssl-dev pkg-config && \
        pecl install mongodb && \
        docker-php-ext-enable mongodb && \
        php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
        php composer-setup.php --install-dir=/usr/local/bin --filename=composer && \
        rm composer-setup.php && \
        composer install --no-dev --optimize-autoloader; \
    fi

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
