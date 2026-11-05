# syntax=docker/dockerfile:1
# ------------------------------------------------------------------------------
# Stage 1: Base Runtime with PDO Extensions
# ------------------------------------------------------------------------------
FROM php:8.3-fpm-alpine AS base

# Install build dependencies and runtime libraries for SQLite and MySQL PDO
RUN apk add --no-cache \
    sqlite-dev \
    sqlite-libs \
    mysql-client \
    bash \
    tzdata \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        pdo_sqlite

WORKDIR /var/www/html

# ------------------------------------------------------------------------------
# Stage 2: Development Runtime
# ------------------------------------------------------------------------------
FROM base AS development

COPY docker/php/conf.d/custom.ini /usr/local/etc/php/conf.d/custom.ini

RUN mkdir -p /var/www/html/storage dev/tmp && \
    chown -R www-data:www-data /var/www/html

EXPOSE 9000
CMD ["php-fpm"]

# ------------------------------------------------------------------------------
# Stage 3: Production Runtime
# ------------------------------------------------------------------------------
FROM base AS production

COPY docker/php/conf.d/custom.ini /usr/local/etc/php/conf.d/custom.ini
COPY src/ /var/www/html/

RUN chown -R www-data:www-data /var/www/html

EXPOSE 9000
CMD ["php-fpm"]
