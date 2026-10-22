
FROM php:8.2-apache

# Install required packages and MongoDB extension
RUN apt-get update && apt-get install -y \
    libssl-dev \
    pkg-config \
    unzip \
    git \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && rm -rf /var/lib/apt/lists/*

# Configure PHP upload limits
RUN printf "upload_max_filesize=5M\npost_max_size=8M\nmax_file_uploads=20\n" \
    > /usr/local/etc/php/conf.d/uploads.ini

# Enable Apache URL rewriting
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html

# Install production dependencies
RUN composer install --no-dev --optimize-autoloader

# Set file permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
