#!/bin/bash
set -e

log() {
    NAME=$1
    LEVEL=$2
    MESSAGE=$3
    TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')
    echo "[$TIMESTAMP] - $NAME - $LEVEL - $MESSAGE"
}

log "entrypoint" "INFO" "Starting entrypoint.sh"

mkdir -p storage/{app,debugbar,framework,logs} \
        storage/framework/{cache,sessions,testing,views}

# Run migrations
if [ "$RUN_MIGRATIONS" = "true" ]; then
    log "entrypoint" "INFO" "Running migrations"
    php artisan migrate --force
fi

# Clear cache
log "entrypoint" "INFO" "Clearing & optimizing cache"
php artisan optimize:clear
php artisan optimize

# Storage symlink
php artisan storage:link || true

# Fix permission
chown -R www-data:www-data storage bootstrap/cache

log "entrypoint" "INFO" "Starting main process"
exec "$@"