# syntax=docker/dockerfile:1
# ─────────────────────────────────────────────────────────────────────────────
# Wowlo production image (Slice 11). PHP is not a native Render runtime, so we
# ship a container. Built in two stages:
#   1) node  — compile the front-end assets (Vite → public/build)
#   2) php   — serversideup/php (nginx + php-fpm + s6) running the Laravel app
#
# serversideup handles nginx, php-fpm, process supervision, non-root user, and
# (via AUTORUN_* env vars set in render.yaml) runs `migrate --force` and caches
# config/routes/views at BOOT — after Render injects the env vars. See
# docs/deployment-slice11-runbook.md.
# ─────────────────────────────────────────────────────────────────────────────

# ── Stage 1: build assets ────────────────────────────────────────────────────
FROM node:20-alpine AS assets
WORKDIR /app

# Cache npm install on the lockfile.
COPY package.json package-lock.json ./
RUN npm ci

# Need the full source (Vite/Tailwind scan resources/views for classes).
COPY . .
RUN npm run build


# ── Stage 2: app image ───────────────────────────────────────────────────────
FROM serversideup/php:8.4-fpm-nginx AS app

# Guarantee the Postgres driver (Neon) + image/intl extensions are present.
# install-php-extensions is idempotent — it skips anything already built in.
USER root
RUN install-php-extensions pdo_pgsql gd intl

WORKDIR /var/www/html

# Composer deps first (cached on the lockfile). No scripts yet — the full app
# source isn't here, and artisan must not run without env at build time.
COPY --chown=www-data:www-data composer.json composer.lock ./
USER www-data
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# App source + the compiled assets from stage 1.
COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=assets /app/public/build ./public/build

# Optimised autoloader over the full source (still no artisan scripts at build).
RUN composer dump-autoload --optimize --no-dev --no-scripts

# serversideup's nginx listens on 8080 (non-root). Render auto-detects this;
# if it can't, set the service port to 8080 in the Render dashboard.
EXPOSE 8080
