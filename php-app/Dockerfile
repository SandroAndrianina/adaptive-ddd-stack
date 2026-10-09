FROM php:8.2-apache

# Apache: enable rewrite, allow .htaccess, point DocumentRoot to CI4 public/
RUN a2enmod rewrite \
 && sed -ri 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf \
 && sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf \
 && sed -ri 's!/var/www/!/var/www/html/public!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# PHP extensions: MySQL + intl (CI4) + gd with JPEG
RUN apt-get update \
 && apt-get install -y --no-install-recommends libicu-dev libpng-dev libjpeg62-turbo-dev \
 && rm -rf /var/lib/apt/lists/* \
 && docker-php-ext-configure gd --with-jpeg \
 && docker-php-ext-install pdo pdo_mysql intl gd

# Raise upload limits
RUN echo "upload_max_filesize=20M" > /usr/local/etc/php/conf.d/uploads.ini \
 && echo "post_max_size=25M"      >> /usr/local/etc/php/conf.d/uploads.ini
RUN mkdir -p /var/www/html/writable/data /var/www/html/writable/uploads

# Copy CI4 source
COPY . /var/www/html/

# Fix writable/ permissions (classic CI4 + Docker trap)
RUN chown -R www-data:www-data /var/www/html/writable \
 && chmod -R 775 /var/www/html/writable

EXPOSE 80