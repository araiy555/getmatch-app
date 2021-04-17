#!/bin/sh
set -e

# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
    set -- php-fpm "$@"
fi

if [ -z "${POSTMILL_SKIP_MIGRATIONS+}" ] && ( \
    [ "$1" = 'php-fpm' ] || \
    [ "$1" = 'php' ] || \
    [ "$1" = 'bin/console' ] \
); then
    RUN_MIGRATIONS=1
fi

if [ -n "$SU_USER" ] && [ "$(id -u)" -eq 0 ]; then
    mkdir -p $POSTMILL_WRITE_DIRS
    setfacl -R -m u:www-data:rwX -m u:"$SU_USER":rwX $POSTMILL_WRITE_DIRS || true
    setfacl -dR -m u:www-data:rwX -m u:"$SU_USER":rwX $POSTMILL_WRITE_DIRS || true
    chmod go+w /proc/self/fd/1 /proc/self/fd/2
    set -- su-exec "$SU_USER" "$@"
fi

if [ -n "$RUN_MIGRATIONS" ] && [ "${APP_ENV:-dev}" = 'dev' ]; then
    echo "Setting up for local development"

    if [ ! -e "vendor/autoload.php" ]; then
        composer install --prefer-dist --no-progress
    fi

    if [ ! -e ".env.local" ]; then
        {
            echo 'DATABASE_URL=pgsql://postmill:secret@db/postmill?serverVersion=11'
            echo "APP_SECRET=$(head /dev/urandom | tr -dc A-Za-z0-9 | head -c 64)"
        } > .env.local
    fi

    if [ ! -e ".env.test.local" ]; then
        {
            echo '# Do *not* set this to your main database, or you could lose data'
            echo 'DATABASE_URL=pgsql://postmill:secret@db/postmill_test?serverVersion=11'
        } > .env.test.local
    fi
fi

if [ -n "$RUN_MIGRATIONS" ]; then
    echo "Waiting for db to be ready..."
    until bin/console doctrine:query:sql "SELECT 1" > /dev/null 2>&1; do
        sleep 1
    done

    bin/console doctrine:migrations:migrate --no-interaction

    if [ "${APP_ENV:-dev}" = 'dev' ] && grep -q DATABASE_URL .env.test.local; then
        bin/console --env=test doctrine:database:drop --force --if-exists
        bin/console --env=test doctrine:database:create
        bin/console --env=test doctrine:migrations:migrate --no-interaction
        bin/console --env=test doctrine:fixtures:load --no-interaction
    fi
fi

exec docker-php-entrypoint "$@"
