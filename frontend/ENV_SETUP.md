# Frontend Environment Setup

## Создание .env файла

После установки зависимостей создайте файл `.env` в корне `frontend/`:

```bash
cd frontend

# Создать .env файл
cat > .env << 'ENVEOF'
VITE_API_BASE_URL=http://localhost:8089
VITE_APP_TITLE=Auth App
ENVEOF
```

Или создайте вручную файл `frontend/.env` с содержимым:

```env
VITE_API_BASE_URL=http://localhost:8089
VITE_APP_TITLE=Auth App
```

## Переменные окружения

### `VITE_API_BASE_URL`
- **Значение**: URL backend API
- **По умолчанию**: `http://localhost:8089`
- **Описание**: Базовый URL для всех API запросов

### `VITE_APP_TITLE`
- **Значение**: Название приложения
- **По умолчанию**: `Auth App`
- **Описание**: Отображается в title и meta tags

## Production

Для production создайте `.env.production`:

```env
VITE_API_BASE_URL=https://your-api-domain.com
VITE_APP_TITLE=Your App Name
```
