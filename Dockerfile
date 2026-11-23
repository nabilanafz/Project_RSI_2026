FROM php:8.3-apache
RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev \
    && docker-php-ext-install pdo_mysql mbstring \
    && rm -rf /var/lib/apt/lists/*
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf \
    && a2enmod headers
COPY php.ini /usr/local/etc/php/conf.d/filkom.ini
COPY --chown=www-data:www-data . /var/www/html
RUN chmod -R 770 /var/www/html/storage
EXPOSE 80
