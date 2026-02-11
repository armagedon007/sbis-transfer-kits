FROM php:8.4-fpm

# Установка системных зависимостей + Node.js
RUN apt-get update && apt-get install -y \
    nginx \
    libxml2-dev \
    libcurl4-openssl-dev \
    libonig-dev \
    curl \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
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

# Копирование приложения
COPY backend /var/www/html/backend
COPY frontend /var/www/html/frontend
COPY package.json /var/www/html/
COPY vite.config.js /var/www/html/

# Установка npm зависимостей и сборка
WORKDIR /var/www/html
RUN npm install && npm run build

# Установка прав доступа
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Удаляем node_modules после сборки для уменьшения размера образа
RUN rm -rf node_modules

# Создание скрипта запуска
RUN echo '#!/bin/bash\n\
php-fpm -D\n\
nginx -g "daemon off;"' > /start.sh \
    && chmod +x /start.sh

EXPOSE 8000

CMD ["/start.sh"]
