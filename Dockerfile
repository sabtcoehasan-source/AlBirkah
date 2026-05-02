# PHP + Apache — متوافق مع Render (يستمع على متغير البيئة PORT)
FROM php:8.2-apache-bookworm

RUN a2enmod rewrite headers

# أداء PHP في الإنتاج
RUN docker-php-ext-install -j"$(nproc)" opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --optimize-autoloader --no-scripts

COPY . .

RUN mkdir -p storage sounds \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R g+w storage

COPY docker/entrypoint.sh /entrypoint.sh
RUN sed -i 's/\r$//' /entrypoint.sh && chmod +x /entrypoint.sh

EXPOSE 8080

ENTRYPOINT ["/entrypoint.sh"]
