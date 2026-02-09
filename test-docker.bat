@echo off
chcp 65001 >nul
echo 🚀 Тестирование Docker конфигурации...
echo.

REM Проверка наличия Docker
echo 1. Проверка Docker...
docker --version >nul 2>&1
if errorlevel 1 (
    echo ✗ Docker не установлен
    exit /b 1
)
echo ✓ Docker установлен

REM Проверка наличия Docker Compose
echo 2. Проверка Docker Compose...
docker-compose --version >nul 2>&1
if errorlevel 1 (
    echo ✗ Docker Compose не установлен
    exit /b 1
)
echo ✓ Docker Compose установлен

REM Проверка структуры проекта
echo 3. Проверка структуры проекта...
if not exist "backend" (
    echo ✗ Отсутствует директория backend
    exit /b 1
)
if not exist "frontend" (
    echo ✗ Отсутствует директория frontend
    exit /b 1
)
echo ✓ Структура проекта корректна

REM Проверка конфигурационных файлов
echo 4. Проверка конфигурационных файлов...
if not exist "Dockerfile" (
    echo ✗ Отсутствует файл Dockerfile
    exit /b 1
)
if not exist "docker-compose.yml" (
    echo ✗ Отсутствует файл docker-compose.yml
    exit /b 1
)
if not exist "nginx.conf" (
    echo ✗ Отсутствует файл nginx.conf
    exit /b 1
)
if not exist "default.conf" (
    echo ✗ Отсутствует файл default.conf
    exit /b 1
)
echo ✓ Все конфигурационные файлы на месте

REM Остановка существующих контейнеров
echo 5. Остановка существующих контейнеров...
docker-compose down >nul 2>&1
echo ✓ Контейнеры остановлены

REM Сборка образа
echo 6. Сборка Docker образа...
docker-compose build
if errorlevel 1 (
    echo ✗ Ошибка при сборке образа
    exit /b 1
)
echo ✓ Образ успешно собран

REM Запуск контейнера
echo 7. Запуск контейнера...
docker-compose up -d
if errorlevel 1 (
    echo ✗ Ошибка при запуске контейнера
    exit /b 1
)
echo ✓ Контейнер запущен

REM Ожидание запуска сервисов
echo 8. Ожидание запуска сервисов...
timeout /t 5 /nobreak >nul

REM Проверка статуса контейнера
echo 9. Проверка статуса контейнера...
docker-compose ps | findstr "Up" >nul
if errorlevel 1 (
    echo ✗ Контейнер не запущен
    docker-compose logs
    exit /b 1
)
echo ✓ Контейнер работает

REM Проверка PHP
echo 10. Проверка PHP...
docker exec php-app php -v >nul 2>&1
if errorlevel 1 (
    echo ✗ PHP не работает
) else (
    echo ✓ PHP работает
    docker exec php-app php -v | findstr "PHP"
)

REM Проверка Nginx
echo 11. Проверка Nginx...
docker exec php-app nginx -t >nul 2>&1
if errorlevel 1 (
    echo ✗ Ошибка в конфигурации Nginx
    docker exec php-app nginx -t
) else (
    echo ✓ Конфигурация Nginx корректна
)

echo.
echo ========================================
echo ✓ Тестирование завершено успешно!
echo ========================================
echo.
echo Приложение доступно по адресу:
echo   Frontend: http://localhost:8000/
echo   API: http://localhost:8000/backend/api/
echo.
echo Для просмотра логов: docker-compose logs -f
echo Для остановки: docker-compose down
echo.
pause
