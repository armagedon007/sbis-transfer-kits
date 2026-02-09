# Docker Setup для PHP приложения

## Структура файлов

- `Dockerfile` - образ с PHP 7.4-FPM и Nginx
- `docker-compose.yml` - конфигурация для запуска контейнера
- `nginx.conf` - основная конфигурация Nginx
- `default.conf` - конфигурация виртуального хоста
- `.dockerignore` - файлы, исключаемые из образа

## Требования

- Docker
- Docker Compose

## Запуск приложения

### 1. Сборка и запуск контейнера

```bash
docker-compose up -d --build
```

### 2. Проверка статуса

```bash
docker-compose ps
```

### 3. Просмотр логов

```bash
# Все логи
docker-compose logs -f

# Логи Nginx
docker exec php-app tail -f /var/log/nginx/access.log
docker exec php-app tail -f /var/log/nginx/error.log
```

## Доступ к приложению

- **Frontend**: http://localhost:8000/
- **API**: http://localhost:8000/backend/api/

### Примеры API endpoints:

- http://localhost:8000/backend/api/warehouses.php
- http://localhost:8000/backend/api/kits.php
- http://localhost:8000/backend/api/products.php
- http://localhost:8000/backend/api/documents.php

## Управление контейнером

### Остановка

```bash
docker-compose stop
```

### Запуск остановленного контейнера

```bash
docker-compose start
```

### Перезапуск

```bash
docker-compose restart
```

### Остановка и удаление контейнера

```bash
docker-compose down
```

### Пересборка образа

```bash
docker-compose up -d --build --force-recreate
```

## Отладка

### Вход в контейнер

```bash
docker exec -it php-app bash
```

### Проверка конфигурации Nginx

```bash
docker exec php-app nginx -t
```

### Проверка PHP

```bash
docker exec php-app php -v
docker exec php-app php -m  # Список установленных модулей
```

### Перезагрузка Nginx без перезапуска контейнера

```bash
docker exec php-app nginx -s reload
```

## Настройка PHP

Если нужно изменить настройки PHP, создайте файл `php.ini` и добавьте в `Dockerfile`:

```dockerfile
COPY php.ini /usr/local/etc/php/conf.d/custom.ini
```

## Volumes

В `docker-compose.yml` настроены volumes для:
- `/backend` - PHP код бэкенда
- `/frontend` - HTML/JS/CSS файлы
- Конфигурационные файлы Nginx

Это позволяет изменять код без пересборки образа.

## Порты

По умолчанию приложение доступно на порту **8000**.

Для изменения порта отредактируйте `docker-compose.yml`:

```yaml
ports:
  - "НОВЫЙ_ПОРТ:8000"
```

## Troubleshooting

### Порт 8000 уже занят

Измените порт в `docker-compose.yml` или остановите процесс, использующий порт 8000.

### Ошибки прав доступа

```bash
docker exec php-app chown -R www-data:www-data /var/www/html
docker exec php-app chmod -R 755 /var/www/html
```

### PHP-FPM не запускается

Проверьте логи:
```bash
docker-compose logs
```

### 502 Bad Gateway

Проверьте, что PHP-FPM запущен:
```bash
docker exec php-app ps aux | grep php-fpm
```

## Производственное использование

Для production рекомендуется:

1. Использовать отдельные контейнеры для Nginx и PHP-FPM
2. Настроить SSL/TLS
3. Использовать переменные окружения для конфигурации
4. Настроить мониторинг и логирование
5. Использовать Docker secrets для чувствительных данных
