FROM php:8.2-fpm-alpine

# تثبيت الإضافات والمكتبات المطلوبة للـ Laravel
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    oniguruma-dev

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# تثبيت Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

# تثبيت مكتبات الباكيند بدون الـ dev dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# تضبيط صلاحيات الفولدرات المهمة في لارافل
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache

# إعداد بورت السيرفر
EXPOSE 80

# أمر التشغيل المباشر للـ لارافل
CMD php artisan serve --host=0.0.0.0 --port=${PORT:-80}
