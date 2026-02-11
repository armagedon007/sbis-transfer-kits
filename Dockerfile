FROM node:22-alpine AS node-builder

WORKDIR /app

# 1. Копируем только файлы зависимостей — кэширование
COPY package.json ./
RUN npm install

# 2. Копируем исходники фронтенда и собираем
COPY frontend ./frontend
COPY vite.config.js ./
RUN npm run build

FROM php:8.4-fpm

# Установка системных зависимостей
RUN apt-get update && apt-get install -y \
    nginx \
    libxml2-dev \
    libcurl4-openssl-dev \
    libonig-dev mc \
    && rm -rf /var/lib/apt/lists/*

# Установка PHP расширений
RUN docker-php-ext-install \
    dom \
    mbstring

# Копирование конфигурации nginx
COPY nginx.conf /etc/nginx/nginx.conf
COPY default.conf /etc/nginx/sites-available/default

# Создание директории для логов
RUN mkdir -p /var/log/nginx /var/log/php-fpm

# 7. Копируем собранный фронтенд из node-builder
COPY --from=node-builder /app/dist ./dist

# Установка прав доступа
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Создание скрипта запуска
RUN echo '#!/bin/bash\n\
php-fpm -D\n\
nginx -g "daemon off;"' > /start.sh \
    && chmod +x /start.sh

WORKDIR /var/www/html

EXPOSE 8000

CMD ["/start.sh"]
