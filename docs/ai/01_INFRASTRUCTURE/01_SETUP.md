# 📦 Фаза 1.1: Руководство по Настройке Инфраструктуры

> **Версия Документа**: 2.0.0
> **Последнее Обновление**: 2025-11-27
> **Предполагаемое Время**: 1-2 дня
> **Сложность**: СРЕДНЯЯ
> **Предварительные Требования**: Базовое знание командной строки, Docker для сервисов приложения

---

## ⚠️ КРИТИЧЕСКИ ВАЖНО: Архитектура AI Сервисов

### Ollama и Whisper устанавливаются НАТИВНО (не в Docker!)

| Компонент | Установка | Причина |
|-----------|-----------|---------|
| **Ollama (LLM)** | Нативная на хосте | Прямой доступ к GPU, 10-20x быстрее |
| **Whisper (STT)** | Нативная на хосте | Прямой доступ к GPU, 10-20x быстрее |
| **Centrifugo, Redis** | Docker | Не требуют GPU |
| **PHP, PostgreSQL, RabbitMQ** | Docker | Стандартная инфраструктура приложения |

### Доступ из Docker контейнеров к нативным сервисам

```bash
# Ollama API
http://host.docker.internal:11434

# Whisper API (faster-whisper-server)
http://host.docker.internal:9001
```

---

## 📋 Содержание

1. [Системные Требования](#системные-требования)
2. [Установка AI Сервисов (Нативная)](#установка-ai-сервисов-нативная)
3. [Настройка Docker Сервисов](#настройка-docker-сервисов)
4. [Проверка и Тестирование](#проверка-и-тестирование)
5. [Устранение Неполадок](#устранение-неполадок)

---

## 🖥️ Системные Требования

### Development (Локальная разработка)

```yaml
Hardware:
  CPU: Apple Silicon (M1/M2/M3/M4) или x86_64
  RAM: 16GB минимум (32GB рекомендуется)
  Storage: 50GB свободного места
  GPU: Встроенная (Apple Silicon) или NVIDIA (Linux)

Software:
  OS: macOS 13+ / Ubuntu 22.04+ / Windows 11 + WSL2
  Docker: 24.0.0+
  Docker Compose: 2.20.0+
  Python: 3.10+ (для faster-whisper)
  Git: 2.34+

AI Модели:
  LLM: Qwen 2.5 7B-14B (q4_K_M квантизация)
  STT: Whisper medium/large-v3
```

### Production (GPU Сервер)

```yaml
Hardware (Рекомендуется RTX 4090):
  GPU: NVIDIA RTX 4090 24GB VRAM
  Memory Bandwidth: 1008 GB/s
  CPU: 8+ ядер
  RAM: 32GB+
  Storage: 100GB SSD

Использование VRAM:
  Qwen 2.5 14B q4_K_M: ~10-12GB
  Whisper large-v3: ~3-4GB
  Итого: ~14-16GB (запас 8GB)

Альтернативные GPU:
  - RTX 4080: 16GB VRAM (достаточно для 14B)
  - A4000: 16GB VRAM (datacenter)
  - 2x V100: 32GB VRAM total (для больших моделей)

Производительность (RTX 4090):
  LLM Response: 3-5 секунд
  STT Processing: 0.5-1 секунда
  Total E2E: < 6 секунд
```

### Порты

```yaml
Нативные AI Сервисы:
  - 11434: Ollama API (нативный)
  - 9001: Whisper API (нативный)

Docker Сервисы:
  - 80, 443: Nginx
  - 3000: Frontend dev server
  - 8089: Backend API
  - 8000: Centrifugo WebSocket
  - 5432/15432: PostgreSQL
  - 5672: RabbitMQ
  - 15672: RabbitMQ Management
  - 6379: Redis
```

---

## 🚀 Установка AI Сервисов (Нативная)

### Шаг 1: Установка Ollama

#### macOS

```bash
# Установка через Homebrew
brew install ollama

# Запуск сервиса
brew services start ollama

# Проверка статуса
brew services info ollama

# Загрузка модели
ollama pull qwen2.5:14b-instruct-q4_K_M
```

#### Linux (Ubuntu/Debian)

```bash
# Установка Ollama
curl -fsSL https://ollama.com/install.sh | sh

# Создание systemd сервиса (автоматически создается при установке)
sudo systemctl enable ollama
sudo systemctl start ollama

# Проверка статуса
sudo systemctl status ollama

# Загрузка модели
ollama pull qwen2.5:14b-instruct-q4_K_M
```

#### Настройка Ollama для Production

```bash
# Создать override файл для systemd
sudo mkdir -p /etc/systemd/system/ollama.service.d/

# Файл: /etc/systemd/system/ollama.service.d/override.conf
cat << 'EOF' | sudo tee /etc/systemd/system/ollama.service.d/override.conf
[Service]
Environment="OLLAMA_HOST=0.0.0.0"
Environment="OLLAMA_NUM_PARALLEL=4"
Environment="OLLAMA_MAX_LOADED_MODELS=2"
Environment="OLLAMA_KEEP_ALIVE=10m"
EOF

# Применить изменения
sudo systemctl daemon-reload
sudo systemctl restart ollama
```

### Шаг 2: Установка Whisper (faster-whisper-server)

#### macOS / Linux

```bash
# Создать виртуальное окружение
python3 -m venv ~/.venv/whisper
source ~/.venv/whisper/bin/activate

# Установка faster-whisper-server
pip install faster-whisper-server

# Запуск сервера (foreground для теста)
faster-whisper-server --host 0.0.0.0 --port 9001 --model large-v3
```

#### Linux с CUDA (Production)

```bash
# Проверка CUDA
nvidia-smi

# Установка с поддержкой CUDA
pip install faster-whisper-server

# Запуск с GPU
faster-whisper-server --host 0.0.0.0 --port 9001 --model large-v3 --device cuda
```

#### Systemd сервис для Whisper (Production)

```bash
# Создать директорию и виртуальное окружение
sudo mkdir -p /opt/whisper
sudo python3 -m venv /opt/whisper/venv
sudo /opt/whisper/venv/bin/pip install faster-whisper-server

# Создать systemd сервис
cat << 'EOF' | sudo tee /etc/systemd/system/faster-whisper.service
[Unit]
Description=Faster Whisper Server
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/opt/whisper
Environment="PATH=/opt/whisper/venv/bin"
ExecStart=/opt/whisper/venv/bin/faster-whisper-server --host 0.0.0.0 --port 9001 --model large-v3 --device cuda
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF

# Активация
sudo systemctl daemon-reload
sudo systemctl enable faster-whisper
sudo systemctl start faster-whisper
```

### Шаг 3: Проверка AI Сервисов

```bash
# Проверка Ollama
curl http://localhost:11434/api/tags
# Ожидаемый ответ: {"models":[{"name":"qwen2.5:14b-instruct-q4_K_M",...}]}

# Тест генерации Ollama
curl -X POST http://localhost:11434/api/generate \
  -H "Content-Type: application/json" \
  -d '{"model":"qwen2.5:14b-instruct-q4_K_M","prompt":"Привет!","stream":false}'

# Проверка Whisper
curl http://localhost:9001/health
# Ожидаемый ответ: {"status":"ok"}

# Тест транскрипции Whisper (с аудио файлом)
curl -X POST http://localhost:9001/v1/audio/transcriptions \
  -F "file=@test.wav" \
  -F "language=ru"
```

---

## 🐳 Настройка Docker Сервисов

### Что остается в Docker

AI сервисы (Ollama, Whisper) работают нативно, Docker используется только для:
- PHP/Symfony backend
- PostgreSQL
- RabbitMQ
- Redis
- Centrifugo
- Nginx
- Frontend (опционально)

### Конфигурация для доступа к нативным сервисам

```bash
# apps/backend/.env
OLLAMA_BASE_URL=http://host.docker.internal:11434
WHISPER_BASE_URL=http://host.docker.internal:9001
```

### Linux: Настройка host.docker.internal

На Linux `host.docker.internal` не работает по умолчанию. Добавьте в docker-compose:

```yaml
services:
  php83-fpm:
    extra_hosts:
      - "host.docker.internal:host-gateway"
```

### Запуск Docker сервисов

```bash
# Из корня проекта
docker-compose up -d

# Проверка статуса
docker-compose ps

# Проверка доступа к нативным сервисам из Docker
docker exec backend-php83 curl http://host.docker.internal:11434/api/tags
docker exec backend-php83 curl http://host.docker.internal:9001/health
```

---

## ✅ Проверка и Тестирование

### Полный Чеклист

```bash
# 1. Нативные AI сервисы
echo "=== Проверка Ollama ==="
curl -s http://localhost:11434/api/tags | jq '.models[].name'

echo "=== Проверка Whisper ==="
curl -s http://localhost:9001/health

# 2. Docker сервисы
echo "=== Docker контейнеры ==="
docker-compose ps

# 3. Доступ из Docker к нативным сервисам
echo "=== Ollama из Docker ==="
docker exec backend-php83 curl -s http://host.docker.internal:11434/api/tags

echo "=== Whisper из Docker ==="
docker exec backend-php83 curl -s http://host.docker.internal:9001/health

# 4. Тест LLM генерации
echo "=== Тест LLM ==="
curl -X POST http://localhost:11434/api/generate \
  -H "Content-Type: application/json" \
  -d '{
    "model": "qwen2.5:14b-instruct-q4_K_M",
    "prompt": "Создай JSON для задачи купить молоко",
    "format": "json",
    "stream": false
  }' | jq '.response'

# 5. Проверка GPU (если есть)
echo "=== GPU Status ==="
nvidia-smi --query-gpu=name,memory.used,memory.total --format=csv 2>/dev/null || echo "No NVIDIA GPU"
```

### Скрипт Проверки Здоровья

```bash
#!/bin/bash
# Файл: scripts/health-check-ai.sh

RED='\033[0;31m'
GREEN='\033[0;32m'
NC='\033[0m'

check() {
    if curl -s -o /dev/null -w "%{http_code}" "$1" | grep -q "200"; then
        echo -e "${GREEN}✓${NC} $2"
        return 0
    else
        echo -e "${RED}✗${NC} $2"
        return 1
    fi
}

echo "=== AI Services Health Check ==="
echo ""

# Нативные сервисы
check "http://localhost:11434/api/tags" "Ollama (native)"
check "http://localhost:9001/health" "Whisper (native)"

# Docker сервисы
check "http://localhost:8089/api" "Backend API (Docker)"
check "http://localhost:8000/health" "Centrifugo (Docker)"

echo ""
echo "=== GPU Status ==="
nvidia-smi --query-gpu=name,memory.used,memory.total --format=csv 2>/dev/null || echo "No NVIDIA GPU (macOS uses Metal)"
```

---

## 🔧 Устранение Неполадок

### Проблема: Ollama не запускается

```bash
# macOS
brew services restart ollama
tail -f ~/Library/Logs/Homebrew/ollama.log

# Linux
sudo systemctl restart ollama
sudo journalctl -u ollama -f
```

### Проблема: Whisper не использует GPU

```bash
# Проверить CUDA
python -c "import torch; print(torch.cuda.is_available())"

# Переустановить с CUDA
pip uninstall faster-whisper-server faster-whisper
pip install faster-whisper-server

# Запустить с явным указанием GPU
faster-whisper-server --device cuda --compute-type float16
```

### Проблема: Docker не видит host.docker.internal

```bash
# Linux: добавить в docker-compose.yml для каждого сервиса
extra_hosts:
  - "host.docker.internal:host-gateway"

# Альтернатива: использовать IP хоста напрямую
docker exec backend-php83 curl http://172.17.0.1:11434/api/tags
```

### Проблема: Недостаточно VRAM

```bash
# Проверить использование VRAM
nvidia-smi

# Использовать меньшую модель
ollama pull qwen2.5:7b-instruct-q4_K_M  # 7B вместо 14B

# Для Whisper: использовать medium вместо large-v3
faster-whisper-server --model medium
```

### Проблема: Медленная генерация LLM

```bash
# Проверить что GPU используется
ollama run qwen2.5:14b-instruct-q4_K_M "test" --verbose

# Увеличить параллелизм (Linux)
sudo mkdir -p /etc/systemd/system/ollama.service.d/
echo '[Service]
Environment="OLLAMA_NUM_PARALLEL=4"' | sudo tee /etc/systemd/system/ollama.service.d/parallel.conf
sudo systemctl daemon-reload
sudo systemctl restart ollama

# macOS
launchctl setenv OLLAMA_NUM_PARALLEL 4
brew services restart ollama
```

---

## 📊 Мониторинг

### Мониторинг GPU

```bash
# Постоянный мониторинг GPU
watch -n 1 nvidia-smi

# Логирование в файл
nvidia-smi --query-gpu=timestamp,name,memory.used,utilization.gpu --format=csv -l 1 > gpu_log.csv
```

### Мониторинг AI сервисов

```bash
# Ollama логи
# macOS
tail -f ~/Library/Logs/Homebrew/ollama.log

# Linux
journalctl -u ollama -f

# Whisper логи
journalctl -u faster-whisper -f
```

---

## 📁 Структура Проекта

```bash
task-manager/                        # Корень проекта
├── apps/
│   ├── backend/                     # Symfony (Docker)
│   │   └── .env                     # OLLAMA_BASE_URL, WHISPER_BASE_URL
│   └── frontend/                    # Vue.js
├── infrastructure/
│   └── docker/                      # Docker configs (без AI сервисов!)
├── docs/
│   └── ai/                          # AI документация
│       └── NATIVE_INSTALLATION.md   # Детальная инструкция
├── docker-compose.yml               # Главный compose (без AI)
└── scripts/
    └── health-check-ai.sh           # Проверка AI сервисов
```

---

## ✅ Следующие Шаги

1. ✅ AI сервисы установлены нативно
2. → Перейти к [Docker Конфигурации](02_DOCKER.md) (для сервисов приложения)
3. → Затем [AI Сервисы (детальная настройка)](03_AI_SERVICES.md)
4. → Наконец [Безопасность и Сеть](04_SECURITY.md)

---

**Статус Документа**: Обновлен для нативной архитектуры
**Последнее Тестирование**: 2025-11-27
**Автор**: AI Architecture Team

---

## 📝 История Изменений

### v2.0.0 (2025-11-27)
- **КРИТИЧЕСКОЕ ИЗМЕНЕНИЕ**: Полный переход на нативную установку AI сервисов
- Удалены Docker конфигурации для Ollama и Whisper
- Добавлены инструкции для macOS и Linux
- Добавлены systemd сервисы для production
- Обновлены требования: RTX 4090 24GB для production
- Добавлен раздел по мониторингу GPU

### v1.0.0 (2025-11-08)
- Первоначальная версия (Docker-based)
