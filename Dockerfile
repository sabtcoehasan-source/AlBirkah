# PHP + Apache — متوافق مع Render (يستمع على متغير البيئة PORT)
FROM php:8.2-apache-bookworm

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN a2enmod rewrite headers

# git + unzip: مطلوبان لـ Composer عند جلب الحزم من الأرشيف
# ext-curl: مطلوب من pusher/guzzle — بدونه يفشل `composer install` (فحص المنصة)
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libcurl4-openssl-dev \
    && docker-php-ext-install -j"$(nproc)" curl opcache \
    && rm -rf /var/lib/apt/lists/*

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
