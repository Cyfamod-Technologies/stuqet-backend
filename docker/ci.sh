#!/bin/sh
set -eu

# Run only against the disposable database supplied by CI.
test "${APP_ENV:-}" = testing
test "${DB_DATABASE:-}" = stuqet_ci

composer validate --no-check-publish
composer check-platform-reqs
if ! find app bootstrap config database routes tests -type f -name '*.php' -print0 \
  | xargs -0 -r php -l > /tmp/php-lint.log 2>&1; then
  cat /tmp/php-lint.log
  exit 1
fi
sh -n docker/entrypoint.sh

php artisan package:discover --ansi
php artisan migrate --force --no-interaction
php artisan migrate:local --force --no-interaction

# Verify a second deployment skips the baseline and completed migrations.
php artisan migrate --force --no-interaction
php artisan migrate:local --force --no-interaction

# vendor/bin/phpunit --do-not-cache-result
