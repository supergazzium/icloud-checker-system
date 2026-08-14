# syntax=docker/dockerfile:1.7

# =============================================================================
# Stage 1: Build front-end assets with Vite
# =============================================================================
FROM node:20-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY vite.config.js postcss.config.js tailwind.config.js ./
COPY resources ./resources
COPY public ./public

RUN npm run build


# =============================================================================
# Stage 2: Install PHP dependencies with Composer
# =============================================================================
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

COPY . .

RUN composer dump-autoload --no-dev --optimize --classmap-authoritative


# =============================================================================
# Stage 3: Runtime image - php-fpm + nginx + supervisord
# =============================================================================
FROM php:8.2-fpm-alpine AS runtime

ARG WWWUSER=1000
ARG WWWGROUP=1000

RUN apk add --no-cache \
        nginx \
        supervisor \
        bash \
        curl \
        tzdata \
        icu-libs \
        libpng \
        libjpeg-turbo \
        libwebp \
        freetype \
        libzip \
        oniguruma \
        mysql-client \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        icu-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        libwebp-dev \
        freetype-dev \
        libzip-dev \
        oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mysqli \
        bcmath \
        intl \
        gd \
        zip \
        exif \
        pcntl \
        opcache \
    && apk del .build-deps \
    && rm -rf /var/cache/apk/*

RUN addgroup -g ${WWWGROUP} -S app \
    && adduser -u ${WWWUSER} -S app -G app

WORKDIR /var/www/html

COPY --from=vendor --chown=app:app /app /var/www/html
COPY --from=assets --chown=app:app /app/public/build /var/www/html/public/build

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/zz-opcache.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-www.conf
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/supervisor/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p /var/log/supervisor /run/nginx \
        /tmp/nginx-client-body /tmp/nginx-proxy /tmp/nginx-fastcgi \
        /tmp/nginx-uwsgi /tmp/nginx-scgi \
    && chown -R app:app /run/nginx /tmp/nginx-client-body /tmp/nginx-proxy \
        /tmp/nginx-fastcgi /tmp/nginx-uwsgi /tmp/nginx-scgi \
    && rm -f /etc/nginx/http.d/default.conf.bak \
    && chown -R app:app /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R ug+rwX /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -fsS http://127.0.0.1:8080/up || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
