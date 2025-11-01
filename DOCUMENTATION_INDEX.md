# 📚 Индекс документации проекта

> Центральный навигационный файл для всей документации Task Manager

---

## 🎯 С чего начать?

### Для новых разработчиков
1. **[README.md](./README.md)** - Начни здесь! Обзор проекта и Quick Start
2. **[AI_QUICK_REFERENCE.md](./AI_QUICK_REFERENCE.md)** - Быстрая шпаргалка (5 минут)
3. **[PROJECT_DOCUMENTATION.md](./PROJECT_DOCUMENTATION.md)** - Полная документация (20 минут)

### Для AI-моделей
1. **[AI_QUICK_REFERENCE.md](./AI_QUICK_REFERENCE.md)** - Быстрый вход в контекст
2. **[.cursorrules](./.cursorrules)** - Ключевые правила для Cursor
3. **[PROJECT_DOCUMENTATION.md](./PROJECT_DOCUMENTATION.md)** - Детальная информация

---

## 📖 Основная документация

### 📋 [README.md](./README.md)
**Размер**: ~10 KB | **Время чтения**: 5 минут

**Содержание:**
- Обзор проекта и ключевые возможности
- Технологический стек
- Quick Start Guide (запуск за 5 минут)
- Основные команды
- Структура проекта
- Roadmap

**Когда читать:**
- Первое знакомство с проектом
- Нужно быстро запустить приложение
- Хочешь понять общую картину

---

### ⚡ [AI_QUICK_REFERENCE.md](./AI_QUICK_REFERENCE.md)
**Размер**: ~16 KB | **Время чтения**: 5-10 минут

**Содержание:**
- TL;DR - что это за проект
- Ключевые принципы (оптимистичные обновления, точечное обновление)
- Где что находится (структура файлов)
- Частые задачи и решения
- Частые ошибки и как их избежать
- Дизайн-система (цвета, размеры)
- Быстрый старт для новой фичи
- Полезные команды (copy-paste)

**Когда читать:**
- Нужно быстро войти в контекст
- Забыл где что находится
- Нужно решить типичную задачу
- Хочешь освежить память

**Для кого:**
- AI-модели в Cursor
- Новые разработчики
- Быстрая справка для опытных

---

### 📖 [PROJECT_DOCUMENTATION.md](./PROJECT_DOCUMENTATION.md)
**Размер**: ~29 KB | **Время чтения**: 20-30 минут

**Содержание:**
- Полная архитектура проекта
- Модель данных (Entity схемы)
- API Endpoints (полный список)
- Frontend архитектура (компоненты, stores, composables)
- Оптимистичные обновления (детальная реализация)
- Календарь (логика отображения задач)
- Дизайн-система (полная палитра)
- Интернационализация
- Жизненный цикл задачи
- Производительность и оптимизация
- Соглашения о коде
- Безопасность
- Deployment

**Когда читать:**
- Нужно глубокое понимание проекта
- Работаешь над сложной фичей
- Нужно понять архитектурные решения
- Хочешь изучить best practices

**Для кого:**
- Разработчики, работающие над проектом
- Архитекторы
- Code reviewers
- Документация для команды

---

### 🏛️ [ARCHITECTURE_DECISIONS.md](./ARCHITECTURE_DECISIONS.md)
**Размер**: ~17 KB | **Время чтения**: 15-20 минут

**Содержание:**
- ADR-001: Оптимистичные обновления UI
- ADR-002: Неограниченная вложенность подзадач
- ADR-003: Точечное обновление вместо перезагрузки
- ADR-004: PrimeVue как UI библиотека
- ADR-005: Pinia вместо Vuex
- ADR-006: Docker для backend
- ADR-007: JWT для аутентификации
- ADR-008: TypeScript strict mode
- ADR-009: Композиция через Composables
- ADR-010: Нормализация дат для API
- ADR-011: Mobile-first подход
- ADR-012: Умеренные border-radius
- ADR-013: Единый focus style
- ADR-014: Интернационализация с первого дня
- ADR-015: Анимированный загрузочный экран

**Когда читать:**
- Хочешь понять "почему" а не только "как"
- Принимаешь архитектурные решения
- Нужно обосновать выбор технологии
- Изучаешь best practices

**Для кого:**
- Архитекторы
- Tech leads
- Разработчики, принимающие решения
- Новые члены команды

---

### 🔧 [TROUBLESHOOTING.md](./TROUBLESHOOTING.md)
**Размер**: ~21 KB | **Время чтения**: По необходимости

**Содержание:**
- Frontend проблемы (npm, TypeScript, UI)
- Backend проблемы (Symfony, JWT, CORS)
- Docker проблемы (контейнеры, порты)
- База данных (подключение, миграции)
- Аутентификация (токены, OAuth)
- API проблемы (404, 422, валидация)
- UI/UX проблемы (рендеринг, анимации)
- Производительность (оптимизация)
- Последняя надежда (полная перезагрузка)

**Когда читать:**
- Что-то сломалось
- Получаешь ошибку
- Приложение работает не так как ожидалось
- Нужно быстро найти решение

**Для кого:**
- Все разработчики
- Первая помощь при проблемах
- Справочник по типичным ошибкам

---

### ⚙️ [.cursorrules](./.cursorrules)
**Размер**: ~5 KB | **Время чтения**: 3-5 минут

**Содержание:**
- Ключевые принципы (краткая версия)
- Naming conventions
- Частые задачи (quick reference)
- Частые ошибки
- Дизайн (цвета, размеры)
- Команды (copy-paste)
- Best practices

**Когда читать:**
- Работаешь в Cursor
- Нужны правила для AI
- Хочешь быструю справку

**Для кого:**
- AI-модели в Cursor
- Разработчики, использующие Cursor
- Quick reference

---

## 🗺️ Навигация по темам

### Архитектура
- **Общая архитектура**: [PROJECT_DOCUMENTATION.md](./PROJECT_DOCUMENTATION.md#архитектура-проекта)
- **Архитектурные решения**: [ARCHITECTURE_DECISIONS.md](./ARCHITECTURE_DECISIONS.md)
- **Frontend архитектура**: [PROJECT_DOCUMENTATION.md](./PROJECT_DOCUMENTATION.md#frontend-архитектура)
- **Backend архитектура**: [AI_QUICK_REFERENCE.md](./AI_QUICK_REFERENCE.md#где-что-находится)

### Модель данных
- **Entity схемы**: [PROJECT_DOCUMENTATION.md](./PROJECT_DOCUMENTATION.md#модель-данных)
- **Task структура**: [AI_QUICK_REFERENCE.md](./AI_QUICK_REFERENCE.md#task-структура)
- **Связи между Entity**: [PROJECT_DOCUMENTATION.md](./PROJECT_DOCUMENTATION.md#модель-данных)

### API
- **Все endpoints**: [PROJECT_DOCUMENTATION.md](./PROJECT_DOCUMENTATION.md#api-endpoints)
- **Аутентификация**: [PROJECT_DOCUMENTATION.md](./PROJECT_DOCUMENTATION.md#аутентификация-и-авторизация)
- **Фильтрация**: [PROJECT_DOCUMENTATION.md](./PROJECT_DOCUMENTATION.md#фильтрация-и-сортировка)

### Frontend
- **Компоненты**: [PROJECT_DOCUMENTATION.md](./PROJECT_DOCUMENTATION.md#структура-компонентов)
- **Stores**: [PROJECT_DOCUMENTATION.md](./PROJECT_DOCUMENTATION.md#pinia-stores)
- **Composables**: [PROJECT_DOCUMENTATION.md](./PROJECT_DOCUMENTATION.md#composables)
- **Оптимистичные обновления**: [PROJECT_DOCUMENTATION.md](./PROJECT_DOCUMENTATION.md#оптимистичные-обновления-ui)

### Дизайн
- **Дизайн-система**: [PROJECT_DOCUMENTATION.md](./PROJECT_DOCUMENTATION.md#дизайн-система)
- **Цвета и размеры**: [AI_QUICK_REFERENCE.md](./AI_QUICK_REFERENCE.md#дизайн-система-быстрая-справка)
- **Mobile-first**: [ARCHITECTURE_DECISIONS.md](./ARCHITECTURE_DECISIONS.md#adr-011-mobile-first-подход)

### Разработка
- **Quick Start**: [README.md](./README.md#quick-start)
- **Соглашения о коде**: [PROJECT_DOCUMENTATION.md](./PROJECT_DOCUMENTATION.md#соглашения-о-коде)
- **Git workflow**: [README.md](./README.md#git-workflow)
- **Команды**: [AI_QUICK_REFERENCE.md](./AI_QUICK_REFERENCE.md#полезные-команды-копируй-и-вставляй)

### Troubleshooting
- **Frontend проблемы**: [TROUBLESHOOTING.md](./TROUBLESHOOTING.md#frontend-проблемы)
- **Backend проблемы**: [TROUBLESHOOTING.md](./TROUBLESHOOTING.md#backend-проблемы)
- **Docker проблемы**: [TROUBLESHOOTING.md](./TROUBLESHOOTING.md#docker-проблемы)
- **Частые ошибки**: [AI_QUICK_REFERENCE.md](./AI_QUICK_REFERENCE.md#частые-ошибки-и-как-их-избежать)

---

## 📊 Матрица документации

| Документ | Размер | Время | Аудитория | Цель |
|----------|--------|-------|-----------|------|
| README.md | 10 KB | 5 мин | Все | Первое знакомство |
| AI_QUICK_REFERENCE.md | 16 KB | 5-10 мин | AI, новички | Быстрый старт |
| PROJECT_DOCUMENTATION.md | 29 KB | 20-30 мин | Разработчики | Полное понимание |
| ARCHITECTURE_DECISIONS.md | 17 KB | 15-20 мин | Архитекторы | Обоснование решений |
| TROUBLESHOOTING.md | 21 KB | По необходимости | Все | Решение проблем |
| .cursorrules | 5 KB | 3-5 мин | AI в Cursor | Правила для AI |

---

## 🎓 Рекомендуемый порядок изучения

### Уровень 1: Новичок (30 минут)
1. [README.md](./README.md) - 5 минут
2. [AI_QUICK_REFERENCE.md](./AI_QUICK_REFERENCE.md) - 10 минут
3. Запустить проект по Quick Start - 15 минут

**Результат**: Понимаешь что это за проект, можешь запустить локально

---

### Уровень 2: Разработчик (1 час)
1. Уровень 1 - 30 минут
2. [PROJECT_DOCUMENTATION.md](./PROJECT_DOCUMENTATION.md) - 30 минут

**Результат**: Понимаешь архитектуру, можешь начать разработку

---

### Уровень 3: Архитектор (2 часа)
1. Уровень 2 - 1 час
2. [ARCHITECTURE_DECISIONS.md](./ARCHITECTURE_DECISIONS.md) - 20 минут
3. Изучение кодовой базы - 40 минут

**Результат**: Понимаешь "почему", можешь принимать архитектурные решения

---

### Уровень 4: Эксперт (3+ часа)
1. Уровень 3 - 2 часа
2. [TROUBLESHOOTING.md](./TROUBLESHOOTING.md) - 30 минут
3. Глубокое изучение кода - 1+ час

**Результат**: Знаешь проект как свои пять пальцев, можешь решить любую проблему

---

## 🔍 Поиск по документации

### Ищешь информацию о...

**Задачах (Tasks)**
- Модель: [PROJECT_DOCUMENTATION.md](./PROJECT_DOCUMENTATION.md#task-задача)
- API: [PROJECT_DOCUMENTATION.md](./PROJECT_DOCUMENTATION.md#tasks)
- Компоненты: [PROJECT_DOCUMENTATION.md](./PROJECT_DOCUMENTATION.md#task-components)

**Подзадачах (Subtasks)**
- Архитектура: [ARCHITECTURE_DECISIONS.md](./ARCHITECTURE_DECISIONS.md#adr-002-неограниченная-вложенность-подзадач)
- Логика: [PROJECT_DOCUMENTATION.md](./PROJECT_DOCUMENTATION.md#composables)
- Проблемы: [TROUBLESHOOTING.md](./TROUBLESHOOTING.md#проблема-подзадачи-не-обновляются-в-реальном-времени)

**Календаре**
- Реализация: [PROJECT_DOCUMENTATION.md](./PROJECT_DOCUMENTATION.md#календарь---особенности-реализации)
- Логика дат: [PROJECT_DOCUMENTATION.md](./PROJECT_DOCUMENTATION.md#логика-отображения-задач-на-дату)
- Проблемы: [TROUBLESHOOTING.md](./TROUBLESHOOTING.md#проблема-задачи-не-фильтруются-по-дате)

**Оптимистичных обновлениях**
- Концепция: [PROJECT_DOCUMENTATION.md](./PROJECT_DOCUMENTATION.md#оптимистичные-обновления-ui)
- ADR: [ARCHITECTURE_DECISIONS.md](./ARCHITECTURE_DECISIONS.md#adr-001-оптимистичные-обновления-ui)
- Реализация: [AI_QUICK_REFERENCE.md](./AI_QUICK_REFERENCE.md#ключевые-принципы-проекта)

**Аутентификации**
- Общее: [PROJECT_DOCUMENTATION.md](./PROJECT_DOCUMENTATION.md#аутентификация-и-авторизация)
- JWT: [ARCHITECTURE_DECISIONS.md](./ARCHITECTURE_DECISIONS.md#adr-007-jwt-для-аутентификации)
- Проблемы: [TROUBLESHOOTING.md](./TROUBLESHOOTING.md#аутентификация)

**Дизайне**
- Система: [PROJECT_DOCUMENTATION.md](./PROJECT_DOCUMENTATION.md#дизайн-система)
- Быстрая справка: [AI_QUICK_REFERENCE.md](./AI_QUICK_REFERENCE.md#дизайн-система-быстрая-справка)
- Решения: [ARCHITECTURE_DECISIONS.md](./ARCHITECTURE_DECISIONS.md#adr-012-умеренные-border-radius)

---

## 📝 Обновление документации

### Когда обновлять

**README.md**
- Изменился Quick Start
- Добавлены новые возможности
- Изменился стек технологий

**AI_QUICK_REFERENCE.md**
- Изменились ключевые принципы
- Добавлены частые задачи
- Обновлены команды

**PROJECT_DOCUMENTATION.md**
- Изменилась архитектура
- Добавлены новые API endpoints
- Изменилась модель данных

**ARCHITECTURE_DECISIONS.md**
- Принято новое архитектурное решение
- Изменен существующий ADR

**TROUBLESHOOTING.md**
- Найдена новая типичная проблема
- Найдено решение существующей проблемы

**.cursorrules**
- Изменились правила для AI
- Добавлены новые best practices

---

## 🤝 Вклад в документацию

### Правила
1. Пиши просто и понятно
2. Добавляй примеры кода
3. Используй эмодзи для навигации
4. Обновляй индекс при добавлении секций
5. Проверяй ссылки

### Структура
```markdown
## Заголовок

**Контекст**: Почему это важно

**Решение**: Что делать

**Пример**:
```code
// Пример кода
```

**Результат**: Что получим
```

---

## 📞 Контакты

Если не нашел ответ в документации:
1. Проверь [TROUBLESHOOTING.md](./TROUBLESHOOTING.md)
2. Посмотри логи
3. Создай issue с подробным описанием

---

**Последнее обновление**: 01.11.2025  
**Версия документации**: 1.0.0

---

*Документация - это код, который читают люди. Пиши её с любовью! ❤️*

