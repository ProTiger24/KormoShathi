# PHP 8.2 with Apache
FROM php:8.2-apache

# Install system dependencies & PHP extensions (For PDF parsing, zip, and image processing)
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mysqli zip gd mbstring

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy application files
COPY . /var/www/html/

# Copy config folder explicitly
COPY config/ /var/www/html/config/

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Create uploads directory
RUN mkdir -p /var/www/html/uploads/resumes && chmod -R 777 /var/www/html/uploads

# PHP configuration
RUN echo "memory_limit=512M" > /usr/local/etc/php/conf.d/custom.ini
RUN echo "upload_max_filesize=20M" >> /usr/local/etc/php/conf.d/custom.ini
RUN echo "post_max_size=20M" >> /usr/local/etc/php/conf.d/custom.ini
RUN echo "max_execution_time=300" >> /usr/local/etc/php/conf.d/custom.ini
RUN echo "error_log = /var/log/php_errors.log" >> /usr/local/etc/php/conf.d/custom.ini
RUN echo "log_errors = On" >> /usr/local/etc/php/conf.d/custom.ini
RUN echo "display_errors = Off" >> /usr/local/etc/php/conf.d/custom.ini

EXPOSE 80
CMD ["apache2-foreground"]