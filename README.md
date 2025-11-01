# 📋 Task Manager - Современное приложение для управления задачами

Полнофункциональное SPA-приложение для управления задачами с поддержкой неограниченной вложенности подзадач, календарем, тегами и оптимистичными обновлениями UI.

## ✨ Ключевые возможности

- ✅ **Управление задачами** с неограниченной вложенностью подзадач
- 📅 **Календарь** с месячным и недельным видом
- 🏷️ **Система тегов** для категоризации задач
- ⚡ **Оптимистичные обновления** - мгновенный отклик UI
- 🔍 **Поиск и фильтрация** задач по различным критериям
- 📊 **Статистика** выполнения задач
- 🌐 **Мультиязычность** (RU/EN)
- 📱 **Mobile-first дизайн** - отлично работает на всех устройствах
- 🔐 **JWT аутентификация** + Google OAuth2
- 🎨 **Современный UI** на базе PrimeVue

## 🛠 Технологический стек

**Backend:**
- Symfony 6.4
- PHP 8.3
- PostgreSQL 15
- Doctrine ORM
- JWT Authentication (LexikJWTAuthenticationBundle)
- Docker

**Frontend:**
- Vue.js 3 (Composition API)
- TypeScript (strict mode)
- Vite
- PrimeVue UI Library
- Pinia (state management)
- Vue Router
- Vue I18n
- Axios

## 📚 Документация

### Для быстрого старта:
- **[⚡ AI Quick Reference](./AI_QUICK_REFERENCE.md)** - Быстрая шпаргалка для AI-моделей
- **[🚀 Quick Start Guide](#-quick-start)** - Запуск проекта за 5 минут

### Полная документация:
- **[📖 Project Documentation](./PROJECT_DOCUMENTATION.md)** - Полная документация проекта
- **[🏛️ Architecture Decisions](./ARCHITECTURE_DECISIONS.md)** - Архитектурные решения и их обоснование
- **[🔧 Troubleshooting Guide](./TROUBLESHOOTING.md)** - Решение типичных проблем

### API Documentation:
- **Backend API Docs**: http://localhost:8000/api/doc (после запуска)

## 🚀 Quick Start

### Предварительные требования
- Docker Desktop (для backend)
- Node.js 18+ (для frontend)

### 1. Запуск Backend

```bash
cd backend

# Запустить контейнеры
docker-compose up -d

# Установить зависимости
docker-compose exec php composer install

# Применить миграции
docker-compose exec php bin/console doctrine:migrations:migrate

# Сгенерировать JWT ключи
docker-compose exec php bin/console lexik:jwt:generate-keypair

# (Опционально) Загрузить тестовые данные
docker-compose exec php bin/console doctrine:fixtures:load
```

### 2. Запуск Frontend

```bash
cd frontend

# Установить зависимости
npm install

# Запустить dev сервер
npm run dev
```

### 3. Готово! 🎉

Откройте в браузере:
- **Frontend**: http://localhost:3000
- **Backend API**: http://localhost:8000/api
- **API Documentation**: http://localhost:8000/api/doc

### Тестовые аккаунты

```
Email: sollent98@gmail.com
Password: Pahan1998

Email: vladislikedev@gmail.com
Password: Pahan1998
```

---

## 🎯 Основные команды

### Backend
```bash
# Создать миграцию
docker-compose exec php bin/console make:migration

# Применить миграции
docker-compose exec php bin/console doctrine:migrations:migrate

# Очистить кэш
docker-compose exec php bin/console cache:clear

# Посмотреть логи
docker-compose logs -f php
```

### Frontend
```bash
# Запустить dev сервер
npm run dev

# Собрать production
npm run build

# Проверить типы TypeScript
npm run type-check

# Lint
npm run lint
```

---

## 📸 Скриншоты

### Главная страница (Dashboard)
Список задач с фильтрами, поиском и статистикой.

### Календарь
Месячный и недельный вид с визуализацией задач.

### Детали задачи
Боковая панель с полной информацией о задаче, подзадачами и древовидной структурой.

### Мобильная версия
Полностью адаптивный дизайн для всех устройств.

---

## 🏗️ Структура проекта

```
test_sonnet45/
├── backend/                    # Symfony backend
│   ├── config/                 # Конфигурация
│   ├── migrations/             # Миграции БД
│   ├── src/
│   │   ├── Controller/         # API контроллеры
│   │   ├── Entity/             # Doctrine entities
│   │   ├── Service/            # Бизнес-логика
│   │   ├── Repository/         # Репозитории
│   │   └── DTO/                # Data Transfer Objects
│   └── docker-compose.yml
│
├── frontend/                   # Vue.js frontend
│   ├── src/
│   │   ├── components/         # Vue компоненты
│   │   ├── views/              # Страницы
│   │   ├── stores/             # Pinia stores
│   │   ├── services/           # API сервисы
│   │   ├── composables/        # Переиспользуемая логика
│   │   ├── types/              # TypeScript типы
│   │   └── i18n/               # Переводы
│   └── package.json
│
├── PROJECT_DOCUMENTATION.md    # Полная документация
├── AI_QUICK_REFERENCE.md       # Быстрая справка для AI
├── ARCHITECTURE_DECISIONS.md   # Архитектурные решения
└── TROUBLESHOOTING.md          # Решение проблем
```

---

## 🎨 Особенности реализации

### Оптимистичные обновления UI
Все операции с задачами выполняются мгновенно - UI обновляется сразу, а запрос на сервер идет в фоне. Это создает ощущение невероятно быстрого приложения.

### Неограниченная вложенность подзадач
Задачи могут содержать подзадачи, которые в свою очередь могут содержать свои подзадачи - без ограничений по глубине вложенности.

### Умный календарь
Календарь автоматически определяет, на каких датах отображать задачу, учитывая `startDate`, `dueDate` и задачи, растянутые между датами.

### Mobile-first подход
Приложение разработано с фокусом на мобильные устройства и отлично работает на экранах любого размера.

---

## 🤝 Для разработчиков

### Соглашения о коде

**Backend (Symfony):**
- Следуем SOLID принципам
- Thin controllers - вся логика в сервисах
- Используем DTO для Request/Response
- TypeHints везде
- Doctrine QueryBuilder вместо DQL

**Frontend (Vue.js):**
- Composition API (`<script setup>`)
- TypeScript strict mode
- Типизируем всё: props, emits, state
- Composables для переиспользуемой логики
- Оптимистичные обновления для всех операций

### Git Workflow
```bash
# Создать feature ветку
git checkout -b feature/new-feature

# Коммит с conventional commits
git commit -m "feat: добавлена поддержка drag & drop"

# Push и создать PR
git push origin feature/new-feature
```

### Commit Convention
- `feat:` - новая функциональность
- `fix:` - исправление бага
- `docs:` - изменения в документации
- `style:` - форматирование кода
- `refactor:` - рефакторинг
- `test:` - добавление тестов
- `chore:` - обновление зависимостей

---

## 🐛 Нашли баг?

1. Проверьте [Troubleshooting Guide](./TROUBLESHOOTING.md)
2. Посмотрите логи:
   ```bash
   # Backend
   docker-compose logs -f php
   
   # Frontend
   # DevTools → Console
   ```
3. Создайте issue с подробным описанием

---

## 📈 Roadmap

### В разработке
- [ ] Drag & Drop в календаре
- [ ] Push уведомления
- [ ] Темная тема
- [ ] Экспорт задач (PDF, Excel)

### Планируется
- [ ] Повторяющиеся задачи
- [ ] Командная работа (shared tasks)
- [ ] Мобильное приложение (Capacitor)
- [ ] Интеграция с Google Calendar

---

## 📄 Лицензия

Проект разработан для личного использования.

---

## 🙏 Благодарности

- [Vue.js](https://vuejs.org/) - прогрессивный JavaScript фреймворк
- [Symfony](https://symfony.com/) - PHP фреймворк для веб-приложений
- [PrimeVue](https://primevue.org/) - UI библиотека для Vue.js
- [Doctrine](https://www.doctrine-project.org/) - ORM для PHP

---

**Версия**: 1.0.0  
**Последнее обновление**: 01.11.2025

---

*Создано с ❤️ для эффективного управления задачами*