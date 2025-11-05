# Централизованная система переводов

## Обзор

Переводы для TaskPriority и TaskStatus централизованы на backend и автоматически применяются ко всем API ответам.

## Архитектура

### Backend

#### 1. Файлы переводов
```
backend/translations/
├── enums.en.yaml  # Английские переводы
└── enums.ru.yaml  # Русские переводы
```

#### 2. Сервисы
- **TranslationService** - Основной сервис для переводов enum'ов
- **EnumTranslatorService** - Используется Normalizer'ом для автоматической сериализации
- **LocaleListener** - Автоматически определяет локаль из заголовка `Accept-Language`

#### 3. API Endpoints
```
GET /api/translations/enums?locale=ru
GET /api/translations/priorities?locale=en
GET /api/translations/statuses?locale=ru
```

### Frontend

#### 1. Автоматические переводы
Все задачи, получаемые с backend, автоматически содержат:
- `priorityLabel` - переведенное название приоритета
- `statusLabel` - переведенное название статуса
- `priority.label` - переведенное название в объекте priority
- `status.label` - переведенное название в объекте status

#### 2. Использование в компонентах
```typescript
// Приоритет задачи с переводом с backend
const label = task.priorityLabel || t(`tasks.priority_${task.priority}`)

// Или из объекта
const label = task.priority.label
```

## Добавление новых переводов

### 1. Добавить в backend/translations/enums.en.yaml:
```yaml
task.priority.critical: "Critical"
task.status.on_hold: "On Hold"
```

### 2. Добавить в backend/translations/enums.ru.yaml:
```yaml
task.priority.critical: "Критический"
task.status.on_hold: "На паузе"
```

### 3. Очистить кэш:
```bash
docker compose exec php83-fpm php bin/console cache:clear
```

## Как работает локализация

1. **Frontend отправляет locale** через заголовок `Accept-Language`
2. **LocaleListener** автоматически устанавливает locale в Request
3. **EnumNormalizer** сериализует enum'ы с переводами
4. **TaskController** добавляет `priorityLabel` и `statusLabel` к DTO
5. **Frontend** использует эти переводы или fallback на локальные

## Преимущества

- ✅ Единый источник истины для переводов
- ✅ Консистентность переводов во всем приложении
- ✅ Легко добавлять новые языки
- ✅ Автоматическая локализация API
- ✅ Fallback механизм на frontend
- ✅ Нет необходимости в перезагрузке страницы при смене языка





