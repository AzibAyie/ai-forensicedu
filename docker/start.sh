#!/bin/sh
set -e

php artisan config:clear
php artisan migrate --force

# config:cache is intentionally skipped: it bakes env values into a static
# file once at container boot, and on Render that boot can race the
# platform attaching manually-set (sync: false) environment variables,
# permanently freezing that container with an empty value for its whole
# lifetime. Reading config live avoids that failure mode; route/view
# caching don't hold secrets and are safe to keep.
php artisan route:cache
php artisan view:cache

exec php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"
