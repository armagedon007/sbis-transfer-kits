# Примеры использования API

После запуска Docker контейнера (`docker-compose up -d`), API будет доступно по адресу `http://localhost:8000/backend/api/`

## Endpoints

### 1. Получение списка складов

**URL:** `http://localhost:8000/backend/api/warehouses.php`

**Метод:** GET

**Пример запроса (curl):**
```bash
curl http://localhost:8000/backend/api/warehouses.php
```

**Пример запроса (JavaScript):**
```javascript
fetch('http://localhost:8000/backend/api/warehouses.php')
  .then(response => response.json())
  .then(data => console.log(data))
  .catch(error => console.error('Error:', error));
```

**Пример ответа:**
```json
{
  "success": true,
  "data": [
    {
      "id": "warehouse-1",
      "name": "Основной склад"
    }
  ]
}
```

---

### 2. Получение списка комплектов

**URL:** `http://localhost:8000/backend/api/kits.php`

**Метод:** GET

**Пример запроса (curl):**
```bash
curl http://localhost:8000/backend/api/kits.php
```

**Пример запроса (JavaScript):**
```javascript
fetch('http://localhost:8000/backend/api/kits.php')
  .then(response => response.json())
  .then(data => console.log(data))
  .catch(error => console.error('Error:', error));
```

---

### 3. Получение списка продуктов

**URL:** `http://localhost:8000/backend/api/products.php`

**Метод:** GET

**Пример запроса (curl):**
```bash
curl http://localhost:8000/backend/api/products.php
```

**Пример запроса (JavaScript):**
```javascript
fetch('http://localhost:8000/backend/api/products.php')
  .then(response => response.json())
  .then(data => console.log(data))
  .catch(error => console.error('Error:', error));
```

---

### 4. Создание документа перемещения

**URL:** `http://localhost:8000/backend/api/documents.php`

**Метод:** POST

**Пример запроса (curl):**
```bash
curl -X POST http://localhost:8000/backend/api/documents.php \
  -H "Content-Type: application/json" \
  -d '{
    "fromWarehouse": "warehouse-1",
    "toWarehouse": "warehouse-2",
    "kits": [
      {
        "id": "kit-1",
        "quantity": 2
      }
    ]
  }'
```

**Пример запроса (JavaScript):**
```javascript
fetch('http://localhost:8000/backend/api/documents.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    fromWarehouse: 'warehouse-1',
    toWarehouse: 'warehouse-2',
    kits: [
      {
        id: 'kit-1',
        quantity: 2
      }
    ]
  })
})
  .then(response => response.json())
  .then(data => console.log(data))
  .catch(error => console.error('Error:', error));
```

**Пример ответа (успех):**
```json
{
  "success": true,
  "data": {
    "documentId": "doc-12345",
    "message": "Документ перемещения создан"
  }
}
```

**Пример ответа (ошибка):**
```json
{
  "success": false,
  "error": "Описание ошибки"
}
```

---

## Тестирование API

### Использование браузера

Для GET запросов можно просто открыть URL в браузере:
- http://localhost:8000/backend/api/warehouses.php
- http://localhost:8000/backend/api/kits.php
- http://localhost:8000/backend/api/products.php

### Использование Postman

1. Создайте новый запрос
2. Выберите метод (GET или POST)
3. Введите URL: `http://localhost:8000/backend/api/[endpoint].php`
4. Для POST запросов добавьте JSON в Body → raw → JSON

### Использование curl

```bash
# GET запрос
curl -v http://localhost:8000/backend/api/warehouses.php

# POST запрос
curl -X POST http://localhost:8000/backend/api/documents.php \
  -H "Content-Type: application/json" \
  -d '{"fromWarehouse":"1","toWarehouse":"2","kits":[]}'
```

---

## CORS

API настроено с поддержкой CORS:
- `Access-Control-Allow-Origin: *`
- `Access-Control-Allow-Methods: GET, POST, OPTIONS`
- `Access-Control-Allow-Headers: Content-Type`

Это позволяет делать запросы с любого домена.

---

## Обработка ошибок

Все endpoints возвращают JSON с полями:
- `success` (boolean) - статус выполнения
- `data` (object/array) - данные при успехе
- `error` (string) - сообщение об ошибке при неудаче

HTTP коды ответа:
- `200` - успешный запрос
- `500` - ошибка сервера

---

## Логирование

Для просмотра логов запросов:

```bash
# Все логи
docker-compose logs -f

# Только логи Nginx
docker exec php-app tail -f /var/log/nginx/access.log

# Только ошибки Nginx
docker exec php-app tail -f /var/log/nginx/error.log
```
