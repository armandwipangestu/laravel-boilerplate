ARG BASE_IMAGE=php:8.4-fpm-alpine

FROM ${BASE_IMAGE}

LABEL org.opencontainers.image.source="https://github.com/armandwipangestu/laravel-boilerplate"
LABEL org.opencontainers.image.description="Laravel Boilerplate"
LABEL org.opencontainers.image.licenses="MIT"

WORKDIR /var/www

# copy project
COPY . .

RUN mkdir -p storage bootstrap/cache

# install dependencies
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction

# Copy entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# permissions
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]