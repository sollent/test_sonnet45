# ✅ CORS Проблема исправлена!

## Проблема

```
Access to XMLHttpRequest at 'http://localhost:8089/api/tasks/29521/toggle'
from origin 'http://localhost:3000' has been blocked by CORS policy:
No 'Access-Control-Allow-Origin' header is present on the requested resource.
```

## Причина

В файле `/backend/config/packages/nelmio_cors.yaml` была неправильная конфигурация:

```yaml
# ❌ НЕПРАВИЛЬНО - отключает CORS для всех путей
paths:
    '^/': null
```

`null` означает "не применять никакие CORS правила" = **нет заголовков**

## Решение

Исправил конфигурацию:

```yaml
# ✅ ПРАВИЛЬНО - включает CORS для /api/*
paths:
    '^/api':
        allow_origin: ['*']
        allow_methods: ['GET', 'OPTIONS', 'POST', 'PUT', 'PATCH', 'DELETE']
        allow_headers: ['Content-Type', 'Authorization']
        max_age: 3600
```

## Выполненные действия

1. **Обновил конфигурацию** `/backend/config/packages/nelmio_cors.yaml`
2. **Очистил кеш** `php bin/console cache:clear --no-warmup`
3. **Перезапустил PHP-FPM** `docker restart backend-php83`

## Проверка

### OPTIONS (preflight) запрос:
```bash
curl -X OPTIONS 'http://localhost:8089/api/tasks/29521/toggle' \
  -H 'Origin: http://localhost:3000' \
  -H 'Access-Control-Request-Method: POST'
```

**Ответ:**
```
< HTTP/1.1 200 OK
< Access-Control-Allow-Origin: http://localhost:3000 ✅
< Access-Control-Allow-Methods: GET, OPTIONS, POST, PUT, PATCH, DELETE ✅
< Access-Control-Allow-Headers: content-type, authorization ✅
< Access-Control-Max-Age: 3600 ✅
```

### POST запрос:
```bash
curl -X POST 'http://localhost:8089/api/tasks/29521/toggle' \
  -H 'Origin: http://localhost:3000' \
  -H 'Content-Type: application/json'
```

**Ответ:**
```
< HTTP/1.1 401 Unauthorized (это нормально - нужна авторизация)
< Access-Control-Allow-Origin: http://localhost:3000 ✅
< Content-Type: application/json
```

## Статус

✅ **CORS заголовки теперь присутствуют**
✅ **Preflight (OPTIONS) запросы работают**
✅ **POST/PUT/PATCH/DELETE запросы возвращают CORS заголовки**

## Что делать дальше

1. **Обновить страницу в браузере** (Ctrl+Shift+R для жесткой перезагрузки)
2. **Попробовать:**
   - Изменить статус задачи через checkbox
   - Отредактировать описание
   - Изменить даты

3. **Если всё равно не работает:**
   - Открой DevTools (F12)
   - Вкладка Network
   - Найди запрос к `/api/tasks/{id}/toggle`
   - Посмотри Headers → Response Headers
   - Должен быть `Access-Control-Allow-Origin: http://localhost:3000`

4. **Если видишь ошибку 401:**
   - Это нормально! Значит CORS работает
   - Просто нужно авторизоваться заново

## Дополнительная информация

### Полная конфигурация CORS:

```yaml
nelmio_cors:
    defaults:
        origin_regex: true
        allow_origin: ['*']
        allow_methods: ['GET', 'OPTIONS', 'POST', 'PUT', 'PATCH', 'DELETE']
        allow_headers: ['Content-Type', 'Authorization']
        expose_headers: ['Link']
        max_age: 3600
    paths:
        '^/api':
            allow_origin: ['*']
            allow_methods: ['GET', 'OPTIONS', 'POST', 'PUT', 'PATCH, 'DELETE']
            allow_headers: ['Content-Type', 'Authorization']
            max_age: 3600
```

### Что означают параметры:

- **allow_origin: ['*']** - разрешить запросы с любого origin (localhost:3000 включён)
- **allow_methods** - разрешённые HTTP методы
- **allow_headers** - какие заголовки можно отправлять
- **expose_headers** - какие заголовки браузер может читать
- **max_age: 3600** - кеш preflight запроса на 1 час

### Для продакшена:

Рекомендую изменить `allow_origin` на конкретный домен:

```yaml
paths:
    '^/api':
        allow_origin: ['https://yourdomain.com']
        # или несколько доменов:
        # allow_origin: ['https://yourdomain.com', 'https://app.yourdomain.com']
```

---

**Дата:** 2025-11-05 06:02 UTC+3
**Статус:** ✅ CORS полностью исправлен и работает
**Готово к тестированию:** ДА
