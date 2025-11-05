FROM php:8.3-fpm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        librabbitmq-dev \
        openssl libssl-dev zlib1g-dev curl git unzip libxml2-dev libpq-dev libzip-dev \
        g++ libicu-dev zip libfreetype6-dev libjpeg-dev libpng-dev libwebp-dev libxpm-dev \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
        --with-xpm \
    && docker-php-ext-install -j$(nproc) gd intl opcache pdo pdo_mysql pdo_pgsql zip \
    && pecl install amqp \
    && docker-php-ext-enable amqp \
    && pecl install apcu \
    && docker-php-ext-enable apcu \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/
RUN install-php-extensions xdebug

ENV TZ=Europe/Minsk

RUN apt-get update && apt-get install -y tzdata && \
    ln -snf /usr/share/zoneinfo/Europe/Minsk /etc/localtime && \
    echo "Europe/Minsk" > /etc/timezone

WORKDIR /var/www/backend-app

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN curl -sS https://get.symfony.com/cli/installer | bash
RUN mv /root/.symfony5/bin/symfony /usr/local/bin/symfony
