ARG COMPOSER_VERSION=2
ARG PHP_VERSION=8.0
ARG NODE_VERSION=14
ARG NGINX_VERSION=1.18

# ======
# Assets
# ======

FROM node:${NODE_VERSION}-alpine AS postmill_assets

WORKDIR /app

COPY assets assets/
COPY .babelrc package.json postcss.config.js webpack.config.js yarn.lock ./

RUN set -eux; \
    apk add --no-cache curl; \
    yarn; \
    yarn run build-prod; \
    yarn cache clean; \
    rm -rf node_modules;


# ========
# Composer
# ========

FROM composer:${COMPOSER_VERSION} AS postmill_composer_cache

WORKDIR /app

COPY composer.json composer.lock ./

RUN set -eux; \
    composer install \
        --ignore-platform-reqs \
        --no-autoloader \
        --no-cache \
        --no-dev \
        --no-progress \
        --no-scripts \
        --prefer-dist;


# ==============
# PHP base image
# ==============

FROM php:${PHP_VERSION}-fpm-alpine AS postmill_php_base

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin

RUN set -eux; \
    install-php-extensions \
        $(php -r 'die(PHP_VERSION_ID < 80000 ? 0 : 1);' && echo "amqp") \
        apcu \
        gd \
        intl \
        opcache \
        pdo_pgsql \
        zip \
    ; \
    apk add --no-cache \
        acl \
        su-exec \
    ; \
    echo 'apc.enable_cli = On' >> "$PHP_INI_DIR/conf.d/zz-postmill.ini";

COPY --from=postmill_composer_cache /usr/bin/composer /usr/bin
COPY docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HTACCESS_PROTECT=0 \
    COMPOSER_HOME="/tmp" \
    COMPOSER_MEMORY_LIMIT=-1 \
    POSTMILL_WRITE_DIRS="\
        /app/public/media/cache \
        /app/public/submission_images \
        /app/var/cache/prod/http_cache \
        /app/var/cache/prod/pools \
        /app/var/log \
        /app/var/sessions \
        /tmp \
    " \
    SU_USER=www-data

ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm"]

WORKDIR /app


# ==========
# PHP (prod)
# ==========

FROM postmill_php_base AS postmill_php

COPY composer.* symfony.lock .env LICENSE ./
COPY assets/fonts.json assets/themes.json assets/
COPY bin/console bin/
COPY config config/
COPY public/index.php public/
COPY --from=postmill_assets /app/public/build/*.json public/build/
COPY src src/
COPY templates templates/
COPY translations translations/
COPY --from=postmill_composer_cache /app/vendor vendor/

ENV APP_ENV=prod \
    DATABASE_URL='pgsql://postmill:secret@db/postmill' \
    LOG_FILE='php://stderr' \
    POSTMILL_WRITE_DIRS="\
        ${POSTMILL_WRITE_DIRS} \
        /app/var/cache/prod/http_cache \
        /app/var/cache/prod/pools \
    "

RUN set -eux; \
    apk add --no-cache --virtual .build-deps \
        git; \
    { \
        echo 'opcache.max_accelerated_files = 20000'; \
        echo 'opcache.validate_timestamps = Off'; \
        echo 'realpath_cache_size = 4096K'; \
        echo 'realpath_cache_ttl = 600'; \
        if php -r 'die(PHP_VERSION_ID >= 70403 ? 0 : 1);'; then \
            echo 'opcache.preload = /app/var/cache/prod/App_KernelProdContainer.preload.php'; \
            echo 'opcache.preload_user = "${SU_USER}"'; \
        fi; \
    } >> "$PHP_INI_DIR/conf.d/zz-postmill.ini"; \
    cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"; \
    composer install \
        --apcu-autoloader \
        --classmap-authoritative \
        --no-dev \
        --prefer-dist; \
    sed -i '/^APP_BRANCH\|APP_VERSION/d' .env; \
    composer dump-env prod; \
    composer clear-cache; \
    apk del --no-network .build-deps;

VOLUME /app/public/media/cache
VOLUME /app/public/submission_images
VOLUME /app/var

ARG APP_BRANCH=""
ARG APP_VERSION=""
ENV APP_BRANCH=${APP_BRANCH} \
    APP_VERSION=${APP_VERSION}


# ==========
# PHP (test)
# ==========

FROM postmill_php AS postmill_php_test

RUN set -eux; \
    install-php-extensions pcov;


# =====
# Nginx
# =====

FROM nginx:${NGINX_VERSION}-alpine AS postmill_web

WORKDIR /app

COPY LICENSE .
COPY docker/nginx/conf.d/default.conf /etc/nginx/conf.d/
COPY docker/nginx/conf.d/gzip.conf /etc/nginx/conf.d/
COPY assets/public/* public/
COPY --from=postmill_assets /app/public/build public/build/
COPY --from=postmill_php /app/public/bundles public/bundles/
COPY --from=postmill_php /app/public/js public/js/


# =========
# PHP (dev)
# =========

FROM postmill_php_base AS postmill_php_debug

RUN set -eux; \
    chmod -R go=u /tmp; \
    apk add --no-cache git; \
    install-php-extensions \
        pcov \
        xdebug \
    ; \
    cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini";

ENV POSTMILL_WRITE_DIRS="\
        ${POSTMILL_WRITE_DIRS} \
        /app/var/cache \
    " \
    XDEBUG_CONFIG="client_host=host.docker.internal client_port=9000 idekey=PHPSTORM" \
    XDEBUG_MODE="off"


# ===========
# Nginx (dev)
# ===========

FROM postmill_web AS postmill_web_debug

RUN set -ex; \
    apk add --no-cache openssl; \
    touch /etc/nginx/ssl.conf; \
    chmod go=u /etc/nginx/ssl.conf;

COPY docker/nginx/docker-entrypoint-debug.sh /usr/local/bin/docker-entrypoint.sh
COPY docker/nginx/conf.d/default-dev.conf /etc/nginx/conf.d/default.conf

EXPOSE 443

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["nginx", "-g", "daemon off;"]
