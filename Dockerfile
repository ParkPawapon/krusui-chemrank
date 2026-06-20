FROM node:26-alpine AS assets

WORKDIR /build

COPY package*.json tailwind.config.js postcss.config.js ./
RUN npm ci

COPY resources ./resources
COPY app/Views ./app/Views
COPY public/assets/js ./public/assets/js
COPY public/assets/ranks ./public/assets/ranks
RUN npm run build

FROM php:8.5-fpm-alpine AS php-runtime

WORKDIR /var/www/html

RUN apk add --no-cache libzip \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        libzip-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql zip \
    && apk del .build-deps

COPY docker/php/php.ini /usr/local/etc/php/conf.d/chemrank-production.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

COPY app ./app
COPY bin ./bin
COPY bootstrap ./bootstrap
COPY composer.json ./composer.json
COPY config ./config
COPY database ./database
COPY domain ./domain
COPY infrastructure ./infrastructure
COPY public ./public
COPY storage/.gitkeep ./storage/.gitkeep
COPY storage/cache/.gitkeep ./storage/cache/.gitkeep
COPY storage/logs/.gitkeep ./storage/logs/.gitkeep
COPY storage/rate-limit/.gitkeep ./storage/rate-limit/.gitkeep

COPY --from=assets /build/public/assets/css/app.css ./public/assets/css/app.css

RUN mkdir -p storage/cache storage/logs storage/rate-limit \
    && chown -R www-data:www-data storage \
    && chmod -R 775 storage

USER www-data

CMD ["php-fpm", "-F"]

FROM nginx:1.31.2-alpine AS nginx-runtime

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY public /var/www/html/public
COPY --from=assets /build/public/assets/css/app.css /var/www/html/public/assets/css/app.css
