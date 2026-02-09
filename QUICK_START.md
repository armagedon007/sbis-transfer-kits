# Быстрый старт Docker

## Запуск приложения одной командой

```bash
docker-compose up -d --build
```

## Проверка работы

Откройте в браузере:
- **Frontend**: http://localhost:8000/
- **API**: http://localhost:8000/backend/api/warehouses.php

## Просмотр логов

```bash
docker-compose logs -f
```

## Остановка

```bash
docker-compose down
```

---

## Что было настроено:

✅ **Dockerfile** - PHP 7.4-FPM + Nginx + необходимые расширения  
✅ **docker-compose.yml** - конфигурация контейнера на порту 8000  
✅ **nginx.conf** - основная конфигурация веб-сервера  
✅ **default.conf** - маршрутизация:
   - `/` → `frontend/index.html`
   - `/backend/api/*` → PHP-FPM
   - `/frontend/*` → статические файлы

## Структура маршрутов

| URL | Обработчик | Описание |
|-----|-----------|----------|
| `/` | Nginx | Открывает `frontend/index.html` |
| `/frontend/*` | Nginx | Статические файлы (HTML, JS, CSS) |
| `/backend/api/*.php` | PHP-FPM | API endpoints |
| `/backend/*.php` | PHP-FPM | Другие PHP файлы |

## Установленные PHP расширения

- ✅ curl
- ✅ json
- ✅ dom
- ✅ mbstring

## Volumes (автоматическое обновление кода)

Изменения в файлах применяются сразу без пересборки:
- `./backend` → `/var/www/html/backend`
- `./frontend` → `/var/www/html/frontend`

## Полная документация

См. файл `DOCKER_README.md` для подробной информации.
