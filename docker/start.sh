#!/bin/bash
set -e

# Render assigns the public port dynamically via $PORT.
# We rewrite the nginx config to listen on it instead of the hardcoded 80.
if [ -n "$PORT" ]; then
  sed -i "s/listen 80;/listen $PORT;/" /etc/nginx/sites-available/default
fi

# Cache Laravel config/routes/views for production performance
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run database migrations (safe to run every deploy; skips already-applied ones)
# Comment this out if you manage migrations manually via Supabase SQL editor.
php artisan migrate --force || true

# Start PHP-FPM in the background, then nginx in the foreground
php-fpm -D
nginx -g "daemon off;"
php artisan passport:install --force
php artisan storage:link --force
php artisan passport:keys --force
chmod 600 storage/oauth-*.key
