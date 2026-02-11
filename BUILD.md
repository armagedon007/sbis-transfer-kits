# Сборка фронтенда

## Запуск контейнера
```bash
docker-compose up --build -d
```

При сборке контейнера автоматически устанавливаются npm зависимости.

## Сборка фронтенда
```bash
docker-compose exec web npm run build
```

## Автопересборка при изменениях
```bash
docker-compose exec web npm run watch
```

## Как это работает:
1. При сборке образа устанавливается Node.js и npm зависимости
2. Исходники в `frontend/` монтируются через volume
3. Сборка запускается через `docker-compose exec web npm run build`
4. Собранные файлы попадают в `dist/` (volume)
5. Nginx отдает `/` из `dist/`, если нет - из `frontend/`

## Workflow:
1. Меняешь файлы в `frontend/`
2. Запускаешь `docker-compose exec web npm run build`
3. Обновляешь страницу

