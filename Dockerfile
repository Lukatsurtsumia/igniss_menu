# webdevops/php-nginx, not Nixpacks — Nixpacks has mis-generated the nginx
# config on this team's other Coolify deploys and crash-looped. No Node/Vite
# stage needed here: this app has no frontend build step at all.
# 8.4, not 8.3: composer.json said ^8.3 but composer.lock actually resolved
# Symfony packages that require php >=8.4.1 (locked against a local PHP 8.4
# install) — building against 8.3 fails composer install outright.
FROM webdevops/php-nginx:8.4-alpine

ENV WEB_DOCUMENT_ROOT=/app/public
ENV PHP_DISPLAY_ERRORS=0

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .

# No config:cache here — it would bake in whatever env values exist at BUILD
# time (none/wrong) and Laravel would then ignore Coolify's real runtime env
# vars entirely, reading the stale cached file instead. route:cache/view:cache
# are safe to bake in — they don't depend on secret/env-sourced values.
RUN composer dump-autoload --optimize \
    && php artisan route:cache \
    && php artisan view:cache \
    && chown -R application:application /app/storage /app/bootstrap/cache
