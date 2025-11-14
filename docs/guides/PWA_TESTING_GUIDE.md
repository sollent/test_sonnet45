# 🧪 Руководство по Тестированию PWA и Offline Кеширования

> **Цель**: Проверить работу Service Worker, кеширования и offline режима
> **Версия**: 1.1
> **Дата**: 2025-11-14

---

## 📋 Оглавление

1. [Как Работает PWA в Проекте](#как-работает-pwa-в-проекте)
2. [Подготовка к Тестированию](#подготовка-к-тестированию)
3. [Тест 1: Проверка Service Worker](#тест-1-проверка-service-worker)
4. [Тест 2: Проверка Precache](#тест-2-проверка-precache)
5. [Тест 3: Проверка Runtime Кеширования API](#тест-3-проверка-runtime-кеширования-api)
6. [Тест 4: Проверка Offline Режима](#тест-4-проверка-offline-режима)
7. [Тест 5: Проверка Обновления Кеша](#тест-5-проверка-обновления-кеша)
8. [Troubleshooting](#troubleshooting)

---

## Как Работает PWA в Проекте

### Компоненты PWA

**1. VitePWA Плагин** (`apps/frontend/vite.config.ts`):

```typescript
VitePWA({
  registerType: 'autoUpdate',  // Автоматическое обновление SW
  includeAssets: ['favicon.ico', 'vite.svg'],
  manifest: {
    name: 'TaskFlow - Система Управления Задачами',
    short_name: 'TaskFlow',
    theme_color: '#3B82F6',
    display: 'standalone',
    // ...
  },
  workbox: {
    globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2}'],
    runtimeCaching: [
      // API calls - NetworkFirst
      { urlPattern: /\/api\//, handler: 'NetworkFirst' },
      // Static assets - CacheFirst
      { urlPattern: /\.(png|jpg|jpeg|svg)$/i, handler: 'CacheFirst' }
    ],
    skipWaiting: true,
    clientsClaim: true
  },
  devOptions: {
    enabled: false  // Отключено в dev режиме!
  }
})
```

**2. Регистрация Service Worker** (`apps/frontend/src/main.ts`):

```typescript
import { registerSW } from 'virtual:pwa-register'

const updateSW = registerSW({
  onNeedRefresh() {
    console.log('[PWA] New content available, updating...')
  },
  onOfflineReady() {
    console.log('[PWA] App ready to work offline')
  },
  onRegistered(registration) {
    console.log('[PWA] Service Worker registered successfully')
    // Проверяем обновления каждые 60 минут
    setInterval(() => {
      registration.update()
    }, 60 * 60 * 1000)
  },
  onRegisterError(error) {
    console.error('[PWA] Service Worker registration failed:', error)
  }
})
```

**3. Nginx Конфигурация** (`apps/frontend/nginx.conf`):

```nginx
# Service Worker НЕ кешируется (для автоматических обновлений)
location ~* \.(?:sw\.js|workbox.*\.js|manifest\.webmanifest)$ {
    expires -1;
    add_header Cache-Control "no-cache, no-store, must-revalidate";
}
```

### Что Кешируется?

**Precache (автоматически при установке SW):**
- Все JS файлы (`js/*.js`)
- Все CSS файлы (`css/*.css`)
- HTML файлы (`index.html`)
- Иконки, SVG, шрифты

**Runtime Cache (при использовании):**
- API запросы (`/api/*`) - NetworkFirst стратегия (1 час TTL)
- Изображения - CacheFirst стратегия (30 дней TTL)
- Шрифты - CacheFirst стратегия (1 год TTL)

### Почему PWA Работает Только в Production?

**Development режим (`npm run dev`):**
- `devOptions.enabled: false` в vite.config.ts
- Service Worker не генерируется
- PWA функциональность отключена

**Production режим (`npm run build`):**
- VitePWA генерирует `sw.js` и `manifest.webmanifest`
- Service Worker регистрируется в `main.ts`
- PWA полностью работает

---

## Подготовка к Тестированию

### 1. Собрать Production Build

PWA работает **только в production mode**!

**Вариант A: Локальная Сборка (npm)**

```bash
# 1. Перейти в директорию frontend
cd apps/frontend

# 2. Собрать для production
npm run build

# ИЛИ если нужен кастомный API URL:
VITE_API_BASE_URL=http://localhost:8089 npm run build
```

**Вариант B: Docker Production Build (рекомендуется)**

```bash
# Собрать production образ с nginx
docker build -f apps/frontend/Dockerfile.prod -t frontend-prod ./apps/frontend

# Образ содержит:
# - Оптимизированный dist/ (~400KB gzip)
# - Nginx для serving
# - Service Worker и PWA манифест
```

**Результат:**
```
✓ built in 13.52s
dist/
  ├── index.html
  ├── sw.js              ← Service Worker
  ├── workbox-*.js       ← Workbox runtime
  ├── manifest.webmanifest  ← PWA манифест
  └── js/*, css/*, images/*
```

### 2. Запустить Production Сервер

Service Worker **НЕ работает** на `npm run dev`! Нужен production сервер.

**Вариант 1: Docker (рекомендуется для реального production окружения)**

```bash
# Запустить весь стек (backend + frontend) в production режиме
docker-compose -f docker-compose.yml \
  -f infrastructure/docker/docker-compose-prod.yml \
  -f infrastructure/docker/docker-compose.frontend-prod.yml up -d

# Доступ:
# - Frontend: http://localhost:8080
# - Backend API: http://localhost:8089/api
```

**Вариант 2: npx serve (локальное тестирование)**

```bash
# Установить serve глобально (один раз)
npm install -g serve

# Запустить production build
cd apps/frontend
serve -s dist -l 3000

# Доступ: http://localhost:3000
```

**Вариант 3: http-server**

```bash
npm install -g http-server
cd apps/frontend
http-server dist -p 3000 -c-1

# Доступ: http://localhost:3000
```

**Примечание:** Docker вариант идентичен production окружению (Nginx + Gzip + правильные заголовки)

### 3. Открыть Chrome DevTools

1. Откройте **Chrome** (или Chromium-based браузер)
2. Перейдите на http://localhost:3000
3. Откройте DevTools (`F12` или `Cmd+Option+I`)
4. Перейдите на вкладку **Application**

---

## Тест 1: Проверка Service Worker

### Шаги:

1. **DevTools → Application → Service Workers**
2. Проверьте статус:

**✅ Ожидаемый результат:**

```
Service Workers
  http://localhost:3000
    ● sw.js
      Status: activated and is running
      Source: http://localhost:3000/sw.js
```

3. **Проверьте события:**

```javascript
// В Console выполните:
navigator.serviceWorker.ready.then(reg => {
  console.log('Service Worker зарегистрирован:', reg.active.scriptURL);
});
```

**Вывод:**
```
Service Worker зарегистрирован: http://localhost:3000/sw.js
```

### Troubleshooting:

❌ **"No service workers found"**
- Проблема: Service Worker не зарегистрирован
- Решение: Проверьте что используете production build (`npm run build`)

❌ **"waiting to activate"**
- Проблема: Старый SW блокирует новый
- Решение: Нажмите **"Skip waiting"** в DevTools

---

## Тест 2: Проверка Precache

### Что Такое Precache?

**Precache** - это файлы которые **автоматически кешируются** при установке Service Worker:
- Все JS файлы (`*.js`)
- Все CSS файлы (`*.css`)
- HTML файлы (`index.html`)
- Иконки, SVG, шрифты

**Цель:** Приложение работает offline **сразу**, даже если никогда не открывали страницу.

### Шаги:

1. **DevTools → Application → Cache Storage**
2. Найдите кеш **"workbox-precache-v2-..."**
3. Разверните и посмотрите список файлов:

**✅ Ожидаемые файлы (~40 штук):**

```
workbox-precache-v2-http://localhost:3000/
  ├── http://localhost:3000/
  ├── http://localhost:3000/index.html
  ├── http://localhost:3000/manifest.webmanifest
  ├── http://localhost:3000/js/index-[hash].js
  ├── http://localhost:3000/js/vue-vendor-[hash].js
  ├── http://localhost:3000/js/primevue-components-[hash].js
  ├── http://localhost:3000/js/echarts-vendor-[hash].js
  ├── http://localhost:3000/css/index-[hash].css
  ├── http://localhost:3000/vite.svg
  └── ... (всего ~40 файлов)
```

4. **Проверьте размер кеша:**

```javascript
// В Console:
caches.open('workbox-precache-v2-http://localhost:3000/').then(cache => {
  cache.keys().then(keys => {
    console.log(`Precache содержит ${keys.length} файлов`);
  });
});
```

**Вывод:**
```
Precache содержит 40 файлов
```

### Что Значит?

🎉 **Все эти файлы доступны OFFLINE!**
- JS/CSS/HTML работают без интернета
- Приложение загружается мгновенно (из кеша)
- Service Worker обновляет кеш автоматически при новом деплое

---

## Тест 3: Проверка Runtime Кеширования API

### Что Такое Runtime Cache?

**Runtime Cache** - это данные которые кешируются **во время работы** приложения:
- API запросы (`/api/tasks`, `/api/analytics`)
- Изображения (аватарки, вложения)
- Шрифты

**Стратегия:** NetworkFirst - всегда пытается загрузить свежие данные, кеш - fallback.

### Шаги:

1. **Авторизуйтесь** в приложении
2. **Перейдите на Dashboard** (загрузятся задачи)
3. **Откройте Analytics** (загрузится аналитика)
4. **DevTools → Application → Cache Storage**
5. Найдите кеш **"api-cache"**

**✅ Ожидаемые записи:**

```
api-cache
  ├── http://localhost:8089/api/tasks?view=all&...
  ├── http://localhost:8089/api/tasks?view=today&...
  ├── http://localhost:8089/api/analytics/dashboard
  ├── http://localhost:8089/api/tags
  └── ... (другие API вызовы)
```

6. **Проверьте ответы:**

Кликните на любой запрос → **Preview** → Должны увидеть JSON данные.

7. **Проверьте через Network:**

- **DevTools → Network**
- Обновите страницу (`Cmd+R`)
- Найдите запрос `/api/tasks`
- Смотрите **Size** колонку:

**✅ Если из кеша:**
```
Size: (from ServiceWorker)
```

**✅ Если из сети:**
```
Size: 15.2 kB
```

### Как Работает NetworkFirst?

```
1. Запрос /api/tasks
2. Service Worker перехватывает запрос
3. Пытается загрузить из СЕТИ (timeout 10 сек)
4. Если успех → Сохраняет в кеш + Возвращает данные
5. Если ошибка → Возвращает из кеша (если есть)
```

**Время жизни кеша: 1 час**
- После 1 часа кеш считается устаревшим
- Но всё равно будет использован если нет интернета!

---

## Тест 4: Проверка Offline Режима

### Цель:

Проверить что **приложение работает БЕЗ интернета** (показывает закешированные данные).

### Подготовка:

**Сначала загрузите данные (с интернетом):**

1. Откройте приложение: http://localhost:3000
2. Авторизуйтесь
3. Откройте **Dashboard** → Загрузятся задачи
4. Откройте **Analytics** → Загрузится аналитика
5. Откройте **Calendar** → Загрузятся события

**✅ Данные закешированы!** (в кеше `api-cache`)

### Тест Offline:

**Способ 1: DevTools Offline Mode**

1. **DevTools → Network → Throttling**
2. Выберите **"Offline"**
3. **Обновите страницу** (`Cmd+R`)

**✅ Ожидаемый результат:**
- Страница **загрузилась** (из precache)
- Задачи **отображаются** (из api-cache)
- Аналитика **показывается** (из api-cache)
- В Network все запросы **"(from ServiceWorker)"**

**Способ 2: Реальное Отключение Интернета**

1. Выключите Wi-Fi / Отключите кабель
2. **Обновите страницу** (`Cmd+R`)
3. Должно работать так же!

**Способ 3: Service Worker Update on Reload**

1. **DevTools → Application → Service Workers**
2. Включите **"Update on reload"**
3. Включите **"Offline"** в Network
4. Обновите страницу

### Что Будет Работать Offline?

**✅ Работает:**
- Просмотр задач (если загружали раньше)
- Просмотр аналитики (если загружали раньше)
- Просмотр календаря (если загружали раньше)
- Навигация по страницам (precache)
- UI компоненты (precache)

**❌ НЕ работает:**
- Создание новых задач (требует API)
- Редактирование задач (требует API)
- Авторизация (требует API)
- Загрузка новых данных (требует сеть)

**🔄 Background Sync (будущее улучшение):**
- Можно добавить фоновую синхронизацию
- Сохранять изменения локально
- Отправлять на сервер когда появится интернет

---

## Тест 5: Проверка Обновления Кеша

### Сценарий:

**Проблема:** Что если обновили код на сервере? Как пользователи получат новую версию?

**Решение:** Service Worker автоматически обновляется!

### Тест:

1. **Измените что-то в коде** (например, текст на странице)

```bash
# Пример: изменить заголовок
echo "UPDATED VERSION" > apps/frontend/public/version.txt
```

2. **Пересоберите:**

```bash
npm run build
```

3. **Перезапустите сервер:**

```bash
serve -s dist -l 3000
```

4. **Обновите страницу в браузере** (`Cmd+R`)

5. **DevTools → Application → Service Workers**

**✅ Ожидаемое поведение:**

```
Service Workers:
  ● sw.js (новый)
    Status: waiting to activate
  ● sw.js (старый)
    Status: activated
```

6. **Закройте ВСЕ вкладки** с приложением
7. **Откройте заново**

**✅ Теперь:**
- Новый Service Worker активирован
- Кеш обновлен
- Видны изменения

### Автоматическое Обновление:

```typescript
// В vite.config.ts:
VitePWA({
  registerType: 'autoUpdate', // ← Автоматически обновляет SW!
  workbox: {
    skipWaiting: true,        // ← Активирует новый SW сразу
    clientsClaim: true        // ← Берет контроль над страницей
  }
})
```

**Означает:**
- При деплое нового кода → Service Worker обновится автоматически
- При следующем обновлении страницы → Будет новая версия
- **НЕ нужно** очищать кеш вручную!

---

## Troubleshooting

### ❌ Service Worker не регистрируется

**Проблема:**
```
Application → Service Workers → "No service workers found"
```

**Решения:**

1. **Проверьте регистрацию в main.ts:**

Service Worker должен быть **явно зарегистрирован** в коде:

```typescript
// apps/frontend/src/main.ts
import { registerSW } from 'virtual:pwa-register'

const updateSW = registerSW({
  onRegistered(registration) {
    console.log('[PWA] Service Worker registered')
  }
})
```

**Если импорта нет → Service Worker НЕ будет работать!**

2. **Проверьте что используете production build:**

```bash
npm run build
# НЕ npm run dev!
```

PWA **отключен в development** режиме (`devOptions.enabled: false` в vite.config.ts).

3. **Проверьте что сервер запущен правильно:**

```bash
# ✅ Правильно (production):
serve -s dist -l 3000
# ИЛИ
docker-compose -f docker-compose.yml \
  -f infrastructure/docker/docker-compose-prod.yml \
  -f infrastructure/docker/docker-compose.frontend-prod.yml up -d

# ❌ Неправильно (dev - PWA не работает):
npm run dev
```

4. **Проверьте HTTPS / localhost:**

Service Worker работает только на:
- `https://` (production)
- `http://localhost` (development)
- `http://127.0.0.1` (development)

❌ НЕ работает на `http://192.168.x.x` или других HTTP!

5. **Проверьте консоль на ошибки:**

```javascript
// Откройте Console в DevTools
// Должны увидеть:
[PWA] Service Worker registered successfully
```

**Если видите ошибку:**
```
Failed to register a ServiceWorker: ...
```
→ Проверьте импорт в main.ts (пункт 1)

6. **Очистите кеш браузера:**

```
DevTools → Application → Storage → Clear site data
```

### ❌ Кеш не обновляется

**Проблема:** Внесли изменения, но видна старая версия.

**Решения:**

1. **Жесткое обновление:**

```
Cmd+Shift+R (Mac)
Ctrl+Shift+R (Windows)
```

2. **Пропустить ожидание (Skip Waiting):**

```
DevTools → Application → Service Workers → "Skip waiting"
```

3. **Очистить кеш вручную:**

```javascript
// В Console:
caches.keys().then(keys => {
  keys.forEach(key => caches.delete(key));
  console.log('Все кеши удалены!');
});
```

4. **Отключить Service Worker:**

```
DevTools → Application → Service Workers → "Bypass for network"
```

### ❌ API запросы не кешируются

**Проблема:** В `api-cache` нет данных.

**Проверьте:**

1. **URL паттерн правильный?**

```typescript
// vite.config.ts:
urlPattern: /^https?:\/\/.*\/api\/.*/i
```

**Должно совпадать:**
```
✅ http://localhost:8089/api/tasks  → Совпадает
✅ https://api.example.com/api/user → Совпадает
❌ http://localhost:8089/tasks      → НЕ совпадает (нет /api/)
```

2. **Статус код 200?**

```typescript
cacheableResponse: {
  statuses: [0, 200]  // Кеширует только успешные ответы!
}
```

3. **CORS настроен?**

Service Worker не может кешировать ответы с CORS ошибками.

### ❌ Offline режим не работает

**Проблема:** При отключении интернета страница не загружается.

**Проверьте:**

1. **Precache работает?**

```javascript
// Console:
caches.keys().then(keys => console.log('Кеши:', keys));
```

Должны увидеть:
```
Кеши: ['workbox-precache-v2-...', 'api-cache', ...]
```

2. **Данные загружались раньше?**

Service Worker кеширует только то что **УЖЕ загружали**!

**Решение:**
- С интернетом: откройте все страницы
- Без интернета: эти страницы будут работать

3. **Проверьте Network:**

```
DevTools → Network → Найдите запрос → Size колонка
```

**Должно быть:**
```
(from ServiceWorker)  ← Из кеша ✅
```

**Если видите:**
```
(failed) net::ERR_INTERNET_DISCONNECTED  ← НЕ из кеша ❌
```

→ Значит Service Worker не перехватил запрос.

---

## 📊 Чеклист Успешного Тестирования

**Перед началом:**
- [ ] Production build собран (`npm run build`)
- [ ] Сервер запущен (`serve -s dist -l 3000`)
- [ ] Chrome DevTools открыты

**Тест 1: Service Worker**
- [ ] Service Worker зарегистрирован
- [ ] Статус: "activated and is running"
- [ ] `navigator.serviceWorker.ready` возвращает регистрацию

**Тест 2: Precache**
- [ ] Cache Storage содержит `workbox-precache-v2-...`
- [ ] ~40 файлов прекешировано (JS, CSS, HTML)
- [ ] `index.html` в кеше

**Тест 3: Runtime Cache (API)**
- [ ] Cache Storage содержит `api-cache`
- [ ] API запросы кешируются (видны в api-cache)
- [ ] Network показывает "(from ServiceWorker)"

**Тест 4: Offline Mode**
- [ ] С интернетом: загрузили все данные
- [ ] Без интернета: страница загружается
- [ ] Без интернета: задачи отображаются (из кеша)
- [ ] Без интернета: аналитика работает (из кеша)

**Тест 5: Обновление**
- [ ] Изменили код → Пересобрали → Новый SW появился
- [ ] После закрытия вкладок → Новый SW активирован
- [ ] Изменения видны

**Все пункты ✅ → PWA работает правильно! 🎉**

---

## 📚 Дополнительные Ресурсы

- [Workbox Strategies](https://developer.chrome.com/docs/workbox/modules/workbox-strategies/)
- [Service Worker Lifecycle](https://web.dev/service-worker-lifecycle/)
- [PWA Testing Checklist](https://web.dev/pwa-checklist/)
- [Chrome DevTools: Service Workers](https://developer.chrome.com/docs/devtools/progressive-web-apps/)

---

**Последнее обновление:** 2025-11-14
**Версия:** 1.1
**Автор:** Claude Code AI

**Изменения v1.1:**
- ✅ Добавлен раздел "Как Работает PWA в Проекте" с объяснением всех компонентов
- ✅ Добавлена информация о регистрации Service Worker в main.ts
- ✅ Обновлен Troubleshooting с проверкой импорта registerSW
- ✅ Добавлены примеры кода из vite.config.ts и main.ts
