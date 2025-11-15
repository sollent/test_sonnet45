# 🚀 Пошаговая Инструкция по Настройке CI/CD

> **Финальный гайд по активации CI/CD системы (95% → 100%)**
> **Время выполнения**: ~40 минут
> **Дата**: 2025-11-15
> **Статус**: Production-Ready

---

## 📋 Содержание

1. [Введение](#введение)
2. [Предварительные Требования](#предварительные-требования)
3. [Шаг 1: Генерация Production Credentials](#шаг-1-генерация-production-credentials)
4. [Шаг 2: Создание SSH Ключа для GitHub Actions](#шаг-2-создание-ssh-ключа-для-github-actions)
5. [Шаг 3: Создание Telegram Бота](#шаг-3-создание-telegram-бота)
6. [Шаг 4: Настройка GitHub Secrets](#шаг-4-настройка-github-secrets)
7. [Шаг 5: Тестовый Запуск CI](#шаг-5-тестовый-запуск-ci)
8. [Шаг 6: Тестовый Manual Deploy](#шаг-6-тестовый-manual-deploy)
9. [Шаг 7: Merge в Main + Автоматический Деплой](#шаг-7-merge-в-main--автоматический-деплой)
10. [Проверка Работоспособности](#проверка-работоспособности)
11. [Troubleshooting](#troubleshooting)

---

## Введение

### Что Уже Готово? ✅

**CI/CD инфраструктура на 95%!**

Готово:
- ✅ **3 GitHub Actions workflows** (CI, Deploy, Rollback)
- ✅ **5 bash скриптов** (автоматизация деплоя)
- ✅ **7 Docker конфигураций** (dev/prod изоляция)
- ✅ **8 документов** (5000+ строк документации)
- ✅ **Production сервер** (VDS 45.129.186.88, task.nesty.by)

Осталось:
- ⚠️ **GitHub Secrets** (12 secrets) - НЕ настроены
- ⚠️ **Telegram бот** - НЕ создан

### Что Мы Получим После Настройки?

**Полностью автоматизированный CI/CD pipeline**:

```
Developer Push → GitHub
    ↓
CI Tests (Automatic)
├─ Backend Tests (PHPUnit, PHPStan, PHP-CS-Fixer)
├─ Frontend Tests (Vitest, TypeScript, Build)
├─ E2E Tests (Playwright)
└─ Security Scan (SAST, Trivy)
    ↓
Merge to Main
    ↓
Automatic Deploy to VDS
├─ SSH to 45.129.186.88
├─ Git pull latest code
├─ Docker rebuild (backend + frontend)
├─ Database migrations
├─ Health checks
└─ Telegram notification ✅
    ↓
Production (task.nesty.by)
```

---

## Предварительные Требования

### Проверка Доступов

**Убедитесь что у вас есть:**

- [ ] **Root SSH доступ** к VDS 45.129.186.88
- [ ] **Admin права** в GitHub репозитории
- [ ] **Telegram аккаунт** (для создания бота)
- [ ] **Существующие credentials**:
  - PostgreSQL password (на VDS)
  - RabbitMQ password (на VDS)
  - Google OAuth Client ID + Secret

### Тест SSH Доступа

```bash
# Проверьте что можете подключиться к VDS
ssh root@45.129.186.88

# Должны увидеть приглашение VDS:
# root@your-vds:~#

# Проверьте путь к проекту (по умолчанию /opt/taskflow)
ls -la /opt/taskflow

# Должны увидеть директории:
# apps/  docs/  infrastructure/  scripts/  ...
```

**Если проблемы с доступом** → см. раздел [Troubleshooting](#troubleshooting)

---

## Шаг 1: Генерация Production Credentials

**Время**: ~5 минут
**Цель**: Сгенерировать недостающие безопасные пароли для production

### 1.1 Запуск Генератора Секретов

```bash
# Из корня проекта
cd /Users/sollent/Desktop/Projects/CLAUDE

# Запуск скрипта генерации
bash scripts/generate-secrets.sh
```

**Ожидаемый вывод:**

```
🔐 Production Secrets Generator for TaskFlow
===============================================

Generating secure secrets...

✅ Secrets generated successfully!

📋 Copy these values to your .env.docker.prod file:

# Production Database
POSTGRES_PASSWORD=h3Kj9mL2pQ...  # (32 characters)

# Production RabbitMQ
RABBITMQ_PASSWORD=p8Rk5vN1qM...  # (32 characters)

# Symfony Configuration
APP_SECRET=a1b2c3d4e5f6...       # (32 hex characters)

# JWT Configuration
JWT_PASSPHRASE=9f8e7d6c5b4a...   # (64 hex characters)

⚠️  IMPORTANT SECURITY NOTES:
- Store these secrets in a secure password manager
- NEVER commit these to git
- Change them regularly (every 90 days recommended)
```

### 1.2 Сохранение Сгенерированных Значений

**КРИТИЧНО**: Скопируйте сгенерированные значения в безопасное место!

**Рекомендуемые варианты:**
- 1Password / LastPass / Bitwarden
- Зашифрованный файл на локальной машине
- Secure Notes в macOS Keychain

**Создайте локальный файл (НЕ в git!):**

```bash
# Создать файл для сохранения (он в .gitignore)
nano ~/taskflow-production-secrets.txt

# Вставьте вывод скрипта + существующие credentials:
```

**Формат файла:**

```
# TaskFlow Production Secrets
# Дата создания: 2025-11-15
# ⚠️ КОНФИДЕНЦИАЛЬНО - НЕ ДЕЛИТЬСЯ!

# 1. PostgreSQL (СУЩЕСТВУЮЩИЙ с VDS)
PROD_POSTGRES_PASSWORD=ваш_существующий_пароль_psql

# 2. RabbitMQ (СУЩЕСТВУЮЩИЙ с VDS)
PROD_RABBITMQ_PASSWORD=ваш_существующий_пароль_rabbitmq

# 3. Symfony APP_SECRET (СГЕНЕРИРОВАН scripts/generate-secrets.sh)
PROD_APP_SECRET=a1b2c3d4e5f6...

# 4. JWT Passphrase (СГЕНЕРИРОВАН scripts/generate-secrets.sh)
PROD_JWT_PASSPHRASE=9f8e7d6c5b4a...

# 5. Google OAuth (СУЩЕСТВУЮЩИЙ)
PROD_GOOGLE_CLIENT_ID=1084991394082-...apps.googleusercontent.com
PROD_GOOGLE_CLIENT_SECRET=GOCSPX-...

# 6. VDS Server Access
VDS_HOST=45.129.186.88
VDS_USER=root
VDS_PROJECT_PATH=/opt/taskflow

# 7. Telegram (будет создано в Шаге 3)
TELEGRAM_BOT_TOKEN=
TELEGRAM_CHAT_ID=
```

**Защитите файл:**

```bash
# Только вы можете читать
chmod 600 ~/taskflow-production-secrets.txt
```

### 1.3 Проверка Контрольного Списка

- [ ] Запущен `scripts/generate-secrets.sh`
- [ ] Скопирован `APP_SECRET` (32 символа hex)
- [ ] Скопирован `JWT_PASSPHRASE` (64 символа hex)
- [ ] Записаны существующие PostgreSQL и RabbitMQ пароли
- [ ] Записаны Google OAuth credentials
- [ ] Файл сохранен в безопасном месте
- [ ] Всего 12 значений подготовлено (2 пока пустые - Telegram)

---

## Шаг 2: Создание SSH Ключа для GitHub Actions

**Время**: ~5 минут
**Цель**: Настроить автоматический SSH доступ к VDS для деплоя

### 2.1 Генерация SSH Ключа

```bash
# ED25519 ключ БЕЗ пароля (для автоматизации)
ssh-keygen -t ed25519 -C "github-actions@taskflow" -f ~/.ssh/github_actions_taskflow -N ""
```

**Ожидаемый вывод:**

```
Generating public/private ed25519 key pair.
Your identification has been saved in /Users/sollent/.ssh/github_actions_taskflow
Your public key has been saved in /Users/sollent/.ssh/github_actions_taskflow.pub
The key fingerprint is:
SHA256:abc123... github-actions@taskflow
```

**Созданные файлы:**
- `~/.ssh/github_actions_taskflow` - **приватный ключ** (для GitHub Secrets)
- `~/.ssh/github_actions_taskflow.pub` - **публичный ключ** (для VDS)

### 2.2 Добавление Публичного Ключа на VDS

**Вариант A: Автоматическое копирование (рекомендуется)**

```bash
# Копирование ключа на VDS
ssh-copy-id -i ~/.ssh/github_actions_taskflow.pub root@45.129.186.88

# Ожидаемый вывод:
# /usr/bin/ssh-copy-id: INFO: attempting to log in...
# Number of key(s) added: 1
```

**Вариант B: Ручное копирование**

```bash
# Показать публичный ключ
cat ~/.ssh/github_actions_taskflow.pub

# Скопировать вывод (начинается с "ssh-ed25519 ...")
# Например: ssh-ed25519 AAAAC3NzaC1lZDI1NTE5... github-actions@taskflow

# Подключиться к VDS
ssh root@45.129.186.88

# На VDS: Добавить ключ
echo "ssh-ed25519 AAAAC3NzaC1lZDI1NTE5... github-actions@taskflow" >> ~/.ssh/authorized_keys

# Проверить права
chmod 600 ~/.ssh/authorized_keys
chmod 700 ~/.ssh

# Выйти с VDS
exit
```

### 2.3 Тестирование SSH Подключения

```bash
# Тест подключения с новым ключом
ssh -i ~/.ssh/github_actions_taskflow root@45.129.186.88 "echo 'SSH connection successful!'"

# Должно вывести:
# SSH connection successful!

# Если требуется пароль или ошибка - см. Troubleshooting!
```

### 2.4 Сохранение Приватного Ключа

**Скопируйте приватный ключ для GitHub Secrets:**

```bash
# Показать приватный ключ (ВЕСЬ ВЫВОД, включая BEGIN/END строки!)
cat ~/.ssh/github_actions_taskflow
```

**Ожидаемый формат:**

```
-----BEGIN OPENSSH PRIVATE KEY-----
b3BlbnNzaC1rZXktdjEAAAAABG5vbmUAAAAEbm9uZQAAAAAAAAABAAAAMwAAAAtz
c2gtZWQyNTUxOQAAACBqYWNrZXRzIGFyZSB0aGUgYmVzdCBmb3IgdGhlIHdpbnRl
... (много строк) ...
-----END OPENSSH PRIVATE KEY-----
```

**Скопируйте ВЕСЬ вывод** (включая BEGIN/END!) и добавьте в файл секретов:

```bash
# Обновить файл секретов
nano ~/taskflow-production-secrets.txt

# Добавить секцию:
# VDS SSH Private Key (для GitHub Secret VDS_SSH_KEY)
VDS_SSH_KEY="-----BEGIN OPENSSH PRIVATE KEY-----
b3BlbnNzaC1rZXktdjEAAAA...
-----END OPENSSH PRIVATE KEY-----"
```

### 2.5 Проверка Контрольного Списка

- [ ] Сгенерирован ключ `github_actions_taskflow`
- [ ] Публичный ключ добавлен на VDS (`~/.ssh/authorized_keys`)
- [ ] SSH подключение с новым ключом работает
- [ ] Приватный ключ скопирован в файл секретов
- [ ] Приватный ключ содержит строки BEGIN/END

---

## Шаг 3: Создание Telegram Бота

**Время**: ~5 минут
**Цель**: Настроить уведомления о деплое через Telegram

### 3.1 Создание Бота через BotFather

**Шаг за шагом:**

1. **Откройте Telegram** на телефоне или десктопе

2. **Найдите @BotFather** (официальный бот Telegram)
   - В поиске введите: `@BotFather`
   - Нажмите на бота с синей галочкой (verified)

3. **Отправьте команду `/newbot`**

4. **Введите имя бота** (отображается в чатах):
   ```
   TaskFlow Deploy Bot
   ```

5. **Введите username бота** (должен заканчиваться на `bot`):
   ```
   taskflow_deploy_bot
   ```

   ⚠️ Если занято - попробуйте:
   - `taskflow_cicd_bot`
   - `your_name_taskflow_bot`

6. **Сохраните токен бота!**

   BotFather ответит сообщением:

   ```
   Done! Congratulations on your new bot. You will find it at
   t.me/taskflow_deploy_bot. You can now add a description...

   Use this token to access the HTTP API:
   7000000000:AAHdqTcvCH1vGWJxfSeofSAs0K5PALDsaw

   Keep your token secure and store it safely, it can be used by
   anyone to control your bot.
   ```

   **Скопируйте строку `7000000000:AAHdqTcvCH1...`** - это ваш `TELEGRAM_BOT_TOKEN`!

### 3.2 Получение Chat ID

**Шаг 1: Отправьте сообщение боту**

```
1. Найдите вашего бота в Telegram (@taskflow_deploy_bot)
2. Нажмите START или отправьте любое сообщение
   Например: "Hello from GitHub Actions!"
```

**Шаг 2: Получите обновления бота**

```bash
# Замените <YOUR_BOT_TOKEN> на ваш токен из предыдущего шага
curl -s "https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getUpdates" | jq .

# Пример:
curl -s "https://api.telegram.org/bot7000000000:AAHdqTcvCH1vGWJxfSeofSAs0K5PALDsaw/getUpdates" | jq .
```

**Ожидаемый ответ:**

```json
{
  "ok": true,
  "result": [
    {
      "update_id": 123456789,
      "message": {
        "message_id": 1,
        "from": {
          "id": 987654321,  // <-- ЭТО ВАШ CHAT ID!
          "is_bot": false,
          "first_name": "Your Name"
        },
        "chat": {
          "id": 987654321,  // <-- И ЭТО ТОЖЕ!
          "first_name": "Your Name",
          "type": "private"
        },
        "date": 1731685200,
        "text": "Hello from GitHub Actions!"
      }
    }
  ]
}
```

**Найдите `"chat": {"id": 987654321}` - это ваш `TELEGRAM_CHAT_ID`!**

**Если нет `jq`:**

```bash
# Без jq (вывод будет без форматирования, но работает)
curl -s "https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getUpdates"

# Найдите вручную строку: "chat":{"id":987654321
```

### 3.3 Тест Уведомления

**Отправьте тестовое сообщение:**

```bash
# Замените YOUR_BOT_TOKEN и YOUR_CHAT_ID
curl -X POST "https://api.telegram.org/bot<YOUR_BOT_TOKEN>/sendMessage" \
  -d "chat_id=<YOUR_CHAT_ID>" \
  -d "text=🚀 CI/CD Test Message from GitHub Actions"

# Пример:
curl -X POST "https://api.telegram.org/bot7000000000:AAHdqTcvCH1.../sendMessage" \
  -d "chat_id=987654321" \
  -d "text=🚀 CI/CD Test Message from GitHub Actions"
```

**Ожидаемый результат:**

```json
{
  "ok": true,
  "result": {
    "message_id": 2,
    "from": {
      "id": 7000000000,
      "is_bot": true,
      "first_name": "TaskFlow Deploy Bot",
      "username": "taskflow_deploy_bot"
    },
    "chat": {
      "id": 987654321,
      "first_name": "Your Name",
      "type": "private"
    },
    "date": 1731685300,
    "text": "🚀 CI/CD Test Message from GitHub Actions"
  }
}
```

**В Telegram должно прийти сообщение от бота!** ✅

### 3.4 Сохранение Credentials

```bash
# Обновить файл секретов
nano ~/taskflow-production-secrets.txt

# Добавить в секцию Telegram:
TELEGRAM_BOT_TOKEN=7000000000:AAHdqTcvCH1vGWJxfSeofSAs0K5PALDsaw
TELEGRAM_CHAT_ID=987654321
```

### 3.5 Проверка Контрольного Списка

- [ ] Бот создан через @BotFather
- [ ] Получен `TELEGRAM_BOT_TOKEN` (формат: `7000000000:AAH...`)
- [ ] Отправлено сообщение боту (нажат START)
- [ ] Получен `TELEGRAM_CHAT_ID` (формат: `987654321`)
- [ ] Тестовое сообщение отправлено успешно
- [ ] Credentials добавлены в файл секретов

---

## Шаг 4: Настройка GitHub Secrets

**Время**: ~10 минут
**Цель**: Добавить все 12 secrets в GitHub Repository

### 4.1 Открытие GitHub Secrets

**Навигация:**

```
1. Откройте GitHub в браузере
2. Перейдите в ваш репозиторий: github.com/your-username/test_sonnet45
3. Settings (вкладка вверху)
4. Secrets and variables (слева в меню)
5. Actions
6. Нажмите "New repository secret"
```

**Прямая ссылка (замените YOUR_USERNAME):**
```
https://github.com/YOUR_USERNAME/test_sonnet45/settings/secrets/actions
```

### 4.2 Добавление Secrets (12 штук)

**Для каждого секрета:**
1. Нажмите **"New repository secret"**
2. Введите **Name** (точное название из таблицы ниже!)
3. Вставьте **Value** из вашего файла `~/taskflow-production-secrets.txt`
4. Нажмите **"Add secret"**

**⚠️ ВАЖНО**: Названия секретов должны быть **ТОЧНЫМИ** (чувствительны к регистру!)

### 4.3 Полный Список Secrets

#### Группа 1: Production Credentials (6 secrets)

| Name | Value из файла | Пример |
|------|---------------|--------|
| `PROD_POSTGRES_PASSWORD` | Существующий пароль PostgreSQL на VDS | `h3Kj9mL2pQ...` |
| `PROD_RABBITMQ_PASSWORD` | Существующий пароль RabbitMQ на VDS | `p8Rk5vN1qM...` |
| `PROD_APP_SECRET` | Сгенерированный APP_SECRET | `a1b2c3d4e5f6...` (32 hex) |
| `PROD_JWT_PASSPHRASE` | Сгенерированный JWT_PASSPHRASE | `9f8e7d6c5b4a...` (64 hex) |
| `PROD_GOOGLE_CLIENT_ID` | Google OAuth Client ID | `1084991394082-...apps.googleusercontent.com` |
| `PROD_GOOGLE_CLIENT_SECRET` | Google OAuth Client Secret | `GOCSPX-eJZwWi_zfPq...` |

#### Группа 2: VDS Server Access (4 secrets)

| Name | Value | Пример |
|------|-------|--------|
| `VDS_HOST` | IP адрес VDS | `45.129.186.88` |
| `VDS_USER` | SSH username | `root` |
| `VDS_SSH_KEY` | **ВЕСЬ** приватный ключ (с BEGIN/END!) | `-----BEGIN OPENSSH PRIVATE KEY-----`<br>`...`<br>`-----END OPENSSH PRIVATE KEY-----` |
| `VDS_PROJECT_PATH` | Путь к проекту на VDS | `/opt/taskflow` |

**⚠️ VDS_SSH_KEY - особое внимание!**

Скопируйте **ПОЛНОСТЬЮ** вывод `cat ~/.ssh/github_actions_taskflow`:

```
-----BEGIN OPENSSH PRIVATE KEY-----
b3BlbnNzaC1rZXktdjEAAAAABG5vbmUAAAAEbm9uZQAAAAAAAAABAAAAMwAAAAtz
c2gtZWQyNTUxOQAAACBqYWNrZXRzIGFyZSB0aGUgYmVzdCBmb3IgdGhlIHdpbnRl
... (все строки!) ...
-----END OPENSSH PRIVATE KEY-----
```

**НЕ добавляйте** лишние переносы строк или пробелы!

#### Группа 3: Telegram Notifications (2 secrets)

| Name | Value из Шага 3 | Пример |
|------|----------------|--------|
| `TELEGRAM_BOT_TOKEN` | Токен от @BotFather | `7000000000:AAHdqTcvCH1vGWJxfSeofSAs0K5PALDsaw` |
| `TELEGRAM_CHAT_ID` | ID из getUpdates | `987654321` |

### 4.4 Проверка Добавленных Secrets

**После добавления всех 12 secrets, проверьте:**

```
Страница Secrets and variables → Actions должна показывать:

Repository secrets (12)
├── PROD_APP_SECRET                    Updated now by you
├── PROD_GOOGLE_CLIENT_ID              Updated now by you
├── PROD_GOOGLE_CLIENT_SECRET          Updated now by you
├── PROD_JWT_PASSPHRASE                Updated now by you
├── PROD_POSTGRES_PASSWORD             Updated now by you
├── PROD_RABBITMQ_PASSWORD             Updated now by you
├── TELEGRAM_BOT_TOKEN                 Updated now by you
├── TELEGRAM_CHAT_ID                   Updated now by you
├── VDS_HOST                           Updated now by you
├── VDS_PROJECT_PATH                   Updated now by you
├── VDS_SSH_KEY                        Updated now by you
└── VDS_USER                           Updated now by you
```

**✅ Все 12 secrets присутствуют!**

### 4.5 Проверка Контрольного Списка

- [ ] Открыта страница GitHub → Settings → Secrets → Actions
- [ ] Добавлены 6 Production Credentials secrets
- [ ] Добавлены 4 VDS Server Access secrets
- [ ] Добавлен VDS_SSH_KEY с BEGIN/END строками
- [ ] Добавлены 2 Telegram Notifications secrets
- [ ] **Всего 12/12 secrets** видны на странице

---

## Шаг 5: Тестовый Запуск CI

**Время**: ~5 минут
**Цель**: Проверить что CI workflow работает

### 5.1 Создание Feature Ветки

```bash
# Убедитесь что в main ветке
git checkout main
git pull origin main

# Создать тестовую ветку
git checkout -b feature/test-ci-setup

# Проверка текущей ветки
git branch
# * feature/test-ci-setup
#   main
```

### 5.2 Тестовое Изменение

```bash
# Создать минимальное изменение
echo "# CI/CD System Test - $(date)" >> README.md

# Проверить изменения
git diff README.md

# Должно показать добавленную строку
```

### 5.3 Commit и Push

```bash
# Добавить изменения
git add README.md

# Коммит с понятным сообщением
git commit -m "test: trigger CI workflow to validate setup"

# Push в GitHub
git push origin feature/test-ci-setup

# Ожидаемый вывод:
# To github.com:your-username/test_sonnet45.git
#  * [new branch]      feature/test-ci-setup -> feature/test-ci-setup
```

### 5.4 Проверка GitHub Actions

**Шаг 1: Открыть Actions Tab**

```
1. Откройте GitHub репозиторий в браузере
2. Перейдите на вкладку "Actions" (вверху)
3. Должен появиться новый workflow "CI Tests"
   С названием коммита: "test: trigger CI workflow..."
```

**Шаг 2: Следить за Выполнением**

```
Кликните на workflow → увидите 4 параллельных jobs:

CI Tests
├── Backend Tests (PHP 8.3)
│   └── Шаги: Checkout → Setup PHP → Install deps → PHPStan → PHP-CS-Fixer → PHPUnit
│
├── Frontend Tests (Node 20)
│   └── Шаги: Checkout → Setup Node → npm ci → Type-check → Vitest → Build
│
├── E2E Tests (Full Stack)
│   └── Шаги: Setup services → Playwright install → Run E2E → Upload artifacts
│
└── Security Scan
    └── Шаги: Security Checker → Trivy scanner → Upload SARIF
```

**Шаг 3: Дождаться Завершения**

Время выполнения: **~3-5 минут**

**Ожидаемый результат:**

```
✅ Backend Tests     Completed (2m 15s)
✅ Frontend Tests    Completed (1m 45s)
✅ E2E Tests         Completed (2m 30s)
✅ Security Scan     Completed (1m 20s)

CI Tests ✅ Success
```

### 5.5 Проверка Логов (Если Ошибки)

**Если job упал (красный крестик):**

```
1. Кликните на failed job
2. Кликните на красный шаг
3. Разверните лог (треугольник слева)
4. Найдите ошибку (обычно в конце)
5. См. раздел Troubleshooting
```

**Частые проблемы:**
- Backend Tests fail → проверьте PHPStan errors
- Frontend Tests fail → проверьте TypeScript errors
- E2E Tests fail → могут падать (они опциональны для первого запуска)
- Security Scan fail → обычно warnings, не критично

### 5.6 Проверка Контрольного Списка

- [ ] Создана ветка `feature/test-ci-setup`
- [ ] Сделан тестовый коммит
- [ ] Push выполнен успешно
- [ ] CI workflow запустился в GitHub Actions
- [ ] Backend Tests прошли (зеленая галочка)
- [ ] Frontend Tests прошли
- [ ] E2E Tests запустились (успех опционален)
- [ ] Security Scan завершился

---

## Шаг 6: Тестовый Manual Deploy

**Время**: ~5 минут
**Цель**: Проверить что деплой работает БЕЗ автоматического триггера

### 6.1 Открытие Deploy Workflow

```
1. GitHub → Actions → "Deploy to Production" (слева)
2. Нажмите "Run workflow" (кнопка справа)
3. Откроется dropdown:

   Use workflow from:
   └── Branch: [выбрать feature/test-ci-setup]

4. Нажмите зеленую кнопку "Run workflow"
```

**⚠️ ВАЖНО**: Выберите **feature/test-ci-setup**, НЕ main!

Это безопасный тест без влияния на production (хотя деплой идет на VDS).

### 6.2 Наблюдение за Деплоем

**Откроется страница выполнения:**

```
Deploy to Production
├── 🚀 Deploy to Production (main job)
    └── Шаги (24 steps):
        1. 📥 Checkout code
        2. 📢 Send deployment start notification        ← Telegram уведомление!
        3. 🔑 Setup SSH key
        4. 📝 Create deployment script
        5. 🚀 Deploy to VDS                             ← SSH подключение
           ├── 🚀 Starting deployment...
           ├── 📥 Pulling latest code...
           ├── 📝 Creating production .env files...
           ├── 🔄 Stopping old containers...
           ├── 🏗️ Building Docker images...             ← ~2-3 минуты
           ├── 🚀 Starting new containers...
           ├── ⏳ Waiting for services to start...
           ├── 📦 Installing PHP dependencies...
           ├── 🗄️ Running database migrations...
           ├── 🔑 Generating JWT keys...
           └── ✅ Deployment completed!
        6. 🏥 Health checks                              ← Проверка работоспособности
           ├── ✅ Frontend is healthy
           ├── ✅ Backend API is healthy
           └── ✅ Database connection is healthy
        7. 🧪 Run smoke tests
           ├── ✅ API health endpoint OK
           └── ✅ Frontend HTML OK
        8. 📢 Send success notification                  ← Telegram: Success!
```

**Время выполнения: ~5-7 минут**

### 6.3 Проверка Telegram Уведомлений

**Должны прийти 2 сообщения:**

**Сообщение 1: Старт деплоя**

```
🚀 Deployment Started

📦 Repository: your-username/test_sonnet45
🌿 Branch: feature/test-ci-setup
👤 Actor: your-username
🔢 Run: #1
⏰ Time: 2025-11-15T12:34:56Z

💬 Commit: test: trigger CI workflow to validate setup
🔗 [View Run](https://github.com/...)
```

**Сообщение 2: Успешное завершение**

```
🎉 Deployment Successful!

✅ All tests passed
✅ Code deployed
✅ Docker images built
✅ Containers restarted
✅ Migrations applied
✅ Health checks passed

🔗 Frontend: https://task.nesty.by
🔗 API: https://api.task.nesty.by

📦 Repository: your-username/test_sonnet45
🔢 Run: #1
⏱ Duration: 5m 23s
```

**Если Telegram сообщения НЕ пришли** → проверьте:
1. Правильность `TELEGRAM_BOT_TOKEN` в Secrets
2. Правильность `TELEGRAM_CHAT_ID` в Secrets
3. Логи шага "Send deployment start notification"

### 6.4 Проверка Production Сайта

```bash
# Frontend
curl -I https://task.nesty.by
# Ожидаем: HTTP/2 200

# Backend API Health
curl https://api.task.nesty.by/api/health
# Ожидаем: {"status":"ok"} или похожее

# В браузере
open https://task.nesty.by
# Должна открыться страница приложения
```

### 6.5 Проверка Контрольного Списка

- [ ] Workflow "Deploy to Production" запущен вручную
- [ ] Выбрана ветка `feature/test-ci-setup`
- [ ] SSH подключение прошло успешно (виден в логах)
- [ ] Docker build завершился без ошибок
- [ ] Миграции выполнены
- [ ] Health checks прошли (3/3)
- [ ] Telegram уведомление "Started" получено
- [ ] Telegram уведомление "Success" получено
- [ ] Сайт task.nesty.by доступен

---

## Шаг 7: Merge в Main + Автоматический Деплой

**Время**: ~5 минут
**Цель**: Проверить автоматический деплой при merge

### 7.1 Создание Pull Request

```
1. GitHub → Pull requests → "New pull request"
2. Сравнение:
   base: main ← compare: feature/test-ci-setup
3. Нажмите "Create pull request"
4. Заголовок: "test: setup and validate CI/CD pipeline"
5. Описание (опционально):

   ## Changes
   - Added test commit to trigger CI/CD
   - Validated GitHub Actions workflows
   - Confirmed deployment to production works

   ## Checklist
   - [x] CI tests pass
   - [x] Manual deploy successful
   - [x] Telegram notifications working

6. Нажмите "Create pull request"
```

### 7.2 Ожидание CI Checks

**GitHub автоматически запустит CI:**

```
Pull Request Checks:
├── ✅ CI Tests / Backend Tests     (2m 15s)
├── ✅ CI Tests / Frontend Tests    (1m 45s)
├── ✅ CI Tests / E2E Tests         (2m 30s)
└── ✅ CI Tests / Security Scan     (1m 20s)

All checks have passed ✅
```

**Дождитесь зеленых галочек!**

### 7.3 Merge Pull Request

```
1. Когда все checks зеленые:
   "This branch has no conflicts with the base branch"
   "All checks have passed"

2. Нажмите "Merge pull request"
3. Подтвердите "Confirm merge"
4. Опционально: "Delete branch" (удалить feature ветку)
```

### 7.4 Автоматический Deploy Триггер

**Сразу после merge:**

```
1. GitHub автоматически перенаправит на main ветку
2. Перейдите в Actions tab
3. Должен запуститься workflow "Deploy to Production"
   Trigger: "push" (НЕ "workflow_dispatch" как в Шаге 6!)

Deploy to Production
└── Commit: "Merge pull request #1 from .../feature/test-ci-setup"
└── Started: just now
```

**Это АВТОМАТИЧЕСКИЙ деплой!** Триггер - merge в main.

### 7.5 Проверка Telegram Уведомлений

**Должны прийти те же 2 сообщения:**

1. 🚀 Deployment Started (branch: main)
2. 🎉 Deployment Successful!

**Разница**: Теперь `Branch: main` (раньше было feature/test-ci-setup)

### 7.6 Финальная Проверка Production

```bash
# Обновить локальный main
git checkout main
git pull origin main

# Проверить последний коммит
git log -1
# Должен быть merge commit

# Проверить сайт
curl -I https://task.nesty.by
# HTTP/2 200 ✅

curl https://api.task.nesty.by/api/health
# {"status":"ok"} ✅

# В браузере
open https://task.nesty.by
# Приложение работает ✅
```

### 7.7 Проверка Контрольного Списка

- [ ] Pull Request создан из feature/test-ci-setup → main
- [ ] CI checks прошли в PR
- [ ] PR merged в main
- [ ] Автоматический "Deploy to Production" запустился
- [ ] Деплой завершился успешно
- [ ] Telegram уведомления получены (2 сообщения)
- [ ] Сайт task.nesty.by работает
- [ ] API доступен на api.task.nesty.by

---

## Проверка Работоспособности

### ✅ CI/CD Система Активна!

**Проверьте что всё работает:**

#### 1. Автоматические CI тесты
```bash
# Push в любую ветку → CI запускается
git checkout -b feature/test-auto-ci
echo "test" > test.txt
git add test.txt
git commit -m "test: auto CI trigger"
git push origin feature/test-auto-ci

# Проверить GitHub Actions → должен запуститься CI Tests
```

#### 2. Автоматический деплой
```bash
# Merge в main → деплой запускается
# (уже проверено в Шаге 7)
```

#### 3. Telegram уведомления
```bash
# Проверить что пришли 2 сообщения:
# - Deployment Started
# - Deployment Successful
```

#### 4. Health checks
```bash
# Frontend
curl -I https://task.nesty.by
# Ожидаем: HTTP/2 200

# Backend API
curl https://api.task.nesty.by/api/health
# Ожидаем: {"status":"ok"}

# Database (через SSH на VDS)
ssh root@45.129.186.88
docker exec backend-php83 php bin/console doctrine:query:sql "SELECT 1" --env=prod
# Ожидаем: успешный запрос
```

#### 5. Manual Rollback (опционально)
```bash
# Если нужно откатиться к предыдущему коммиту:
# 1. GitHub → Actions → "Manual Rollback"
# 2. Run workflow
# 3. Ввести commit SHA
# 4. Ввести причину (например: "Testing rollback")
# 5. Run workflow
```

### 🎯 Полный Чеклист CI/CD Готовности

**Инфраструктура:**
- [x] 3 GitHub Actions workflows созданы (CI, Deploy, Rollback)
- [x] 5 bash скриптов готовы
- [x] 7 Docker конфигураций готовы
- [x] 12 GitHub Secrets настроены

**Тестирование:**
- [x] CI Tests работают (backend, frontend, e2e, security)
- [x] Manual Deploy успешен
- [x] Auto Deploy при merge работает
- [x] Telegram уведомления приходят
- [x] Health checks проходят

**Production:**
- [x] Frontend доступен (task.nesty.by)
- [x] Backend API работает (api.task.nesty.by)
- [x] Database подключена
- [x] SSL/HTTPS активен

**🎉 ПОЗДРАВЛЯЕМ! CI/CD система настроена и работает на 100%!**

---

## Troubleshooting

### Проблема: SSH подключение не работает

**Симптомы:**
```
Permission denied (publickey)
```

**Решение:**

```bash
# 1. Проверить что ключ добавлен на VDS
ssh root@45.129.186.88 "cat ~/.ssh/authorized_keys | grep github-actions"

# Должен вывести публичный ключ
# Если пусто → повторить Шаг 2.2

# 2. Проверить права на VDS
ssh root@45.129.186.88 "ls -la ~/.ssh/"
# Ожидаем:
# drwx------  2 root root 4096 ... .
# -rw-------  1 root root  xxx ... authorized_keys

# Если права неправильные:
ssh root@45.129.186.88 "chmod 700 ~/.ssh && chmod 600 ~/.ssh/authorized_keys"

# 3. Проверить что используется правильный ключ
ssh -i ~/.ssh/github_actions_taskflow root@45.129.186.88 "echo OK"
# Должно вывести: OK
```

---

### Проблема: Telegram уведомления не приходят

**Симптомы:**
- Deploy успешен, но сообщений нет

**Решение:**

```bash
# 1. Проверить токен бота
curl -s "https://api.telegram.org/bot<YOUR_TOKEN>/getMe"
# Должно вернуть информацию о боте

# 2. Проверить Chat ID
curl -s "https://api.telegram.org/bot<YOUR_TOKEN>/getUpdates" | grep chat
# Должно показать ваш chat ID

# 3. Тестовая отправка
curl -X POST "https://api.telegram.org/bot<YOUR_TOKEN>/sendMessage" \
  -d "chat_id=<YOUR_CHAT_ID>" \
  -d "text=Test"

# Должно прийти сообщение в Telegram

# 4. Проверить GitHub Secrets
# GitHub → Settings → Secrets → Actions
# Убедитесь что TELEGRAM_BOT_TOKEN и TELEGRAM_CHAT_ID правильные
```

---

### Проблема: Docker build падает на VDS

**Симптомы:**
```
Error: Docker daemon is not running
или
Error: Cannot connect to Docker daemon
```

**Решение:**

```bash
# На VDS
ssh root@45.129.186.88

# Проверить статус Docker
systemctl status docker
# Если "inactive (dead)":

# Запустить Docker
systemctl start docker
systemctl enable docker

# Проверить что работает
docker ps
# Должен показать список контейнеров

# Проверить docker-compose
docker compose version
# Должно показать версию
```

---

### Проблема: Health checks падают

**Симптомы:**
```
❌ Frontend is not responding
или
❌ Backend API health check failed
```

**Решение:**

```bash
# 1. Проверить логи контейнеров на VDS
ssh root@45.129.186.88

# Frontend logs
docker logs -f frontend-prod
# Ищите ошибки

# Backend logs
docker logs -f backend-nginx
docker logs -f backend-php83

# 2. Проверить что контейнеры запущены
docker ps
# Должны быть:
# - frontend-prod (nginx)
# - backend-nginx
# - backend-php83
# - backend-psql16
# - backend-rabbitmq

# 3. Проверить health endpoints вручную
curl -I http://localhost:3001  # Frontend
curl http://localhost/api/health  # Backend

# 4. Проверить Nginx конфигурацию
docker exec backend-nginx nginx -t
# Должно: syntax is ok, test is successful
```

---

### Проблема: Миграции не выполняются

**Симптомы:**
```
Error during migrations: SQLSTATE[...] Connection refused
```

**Решение:**

```bash
# На VDS
ssh root@45.129.186.88

# 1. Проверить что PostgreSQL запущен
docker ps | grep psql16
# Должен быть running

# 2. Проверить логи PostgreSQL
docker logs backend-psql16
# Ищите ошибки

# 3. Проверить DATABASE_URL в контейнере
docker exec backend-php83 env | grep DATABASE_URL
# Должно содержать правильные credentials

# 4. Тестовый запрос к БД
docker exec backend-php83 php bin/console doctrine:query:sql "SELECT 1" --env=prod
# Должно вернуть: [1 => 1]

# 5. Запустить миграции вручную
docker exec backend-php83 php bin/console doctrine:migrations:migrate --no-interaction --env=prod
```

---

### Проблема: GitHub Secrets не подставляются

**Симптомы:**
```
Error: Required secret VDS_SSH_KEY is not set
```

**Решение:**

```bash
# 1. Проверить что секрет существует
# GitHub → Settings → Secrets → Actions
# Найдите VDS_SSH_KEY в списке

# 2. Проверить название (чувствительно к регистру!)
# Должно быть ТОЧНО: VDS_SSH_KEY
# НЕ: vds_ssh_key, Vds_Ssh_Key

# 3. Пересоздать секрет
# Удалить старый → Add new → Вставить значение

# 4. Проверить workflow syntax
# .github/workflows/deploy-production.yml
# Использование: ${{ secrets.VDS_SSH_KEY }}
# НЕ: ${{ secrets.vds_ssh_key }}
```

---

### Проблема: E2E тесты падают в CI

**Симптомы:**
```
E2E Tests failed
```

**Решение:**

```
E2E тесты могут падать при первом запуске - это нормально!

Причины:
- Требуют изолированную тестовую БД (см. E2E_TEST_ISOLATION_STRATEGY.md)
- Требуют Playwright setup
- Медленнее чем unit тесты

Временное решение:
- Пропустить E2E в CI (добавить if: false в workflow)
- Или закомментировать e2e-tests job в ci.yml

Полное решение:
- Реализовать план из docs/ci-cd-plans/E2E_TEST_ISOLATION_STRATEGY.md
- Создать docker-compose.test.yml
- Настроить Symfony команду app:e2e:seed
```

---

## 🎉 Заключение

**Вы успешно настроили полностью автоматизированную CI/CD систему!**

### Что Теперь Доступно?

✅ **Continuous Integration:**
- Автоматические тесты на каждый push
- Backend: PHPUnit, PHPStan, PHP-CS-Fixer
- Frontend: Vitest, TypeScript check, Build
- Security: SAST, Trivy scanner

✅ **Continuous Deployment:**
- Автоматический деплой при merge в main
- SSH подключение к VDS
- Docker rebuild (backend + frontend)
- Database migrations
- Health checks

✅ **Notifications:**
- Telegram уведомления о каждом деплое
- Информация о статусе (success/failure)
- Прямые ссылки на GitHub Actions logs

✅ **Rollback:**
- Ручной откат к любому коммиту
- Через GitHub Actions UI
- С уведомлениями в Telegram

### Следующие Шаги (Опционально)

**Для дальнейшего улучшения:**

1. **Staging Environment** - тестирование перед production
   - См. `docs/ci-cd-plans/E2E_TEST_ISOLATION_STRATEGY.md`

2. **Monitoring** - Grafana + Prometheus
   - Метрики производительности
   - Alerts при ошибках

3. **Frontend Optimization** - ускорение сборки
   - См. `docs/ci-cd-plans/FRONTEND_OPTIMIZATION_PLAN.md`

4. **Container Registry** - версионирование Docker образов
   - GitHub Container Registry (ghcr.io)

### 📚 Полезные Ресурсы

- **Основной гайд**: `docs/CI_CD_COMPLETE_GUIDE.md`
- **Контекст проекта**: `docs/CI_CD_CONTEXT.md`
- **Deployment**: `docs/guides/DEPLOYMENT.md`
- **HTTPS Setup**: `docs/deployment/HTTPS_SETUP.md`

---

**Готово! Наслаждайтесь автоматизацией! 🚀**

*Последнее обновление: 2025-11-15*
*Версия: 1.0*
*Автор: Claude Code AI*
