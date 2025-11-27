# 🖥️ Нативная Установка AI Сервисов

> **Версия**: 2.0.0
> **Дата**: 2025-11-27
> **Статус**: Production-ready

---

## ⚠️ КРИТИЧЕСКИ ВАЖНО

**AI сервисы (Ollama и Whisper) устанавливаются НАТИВНО на хост-машину!**

```
НЕ Docker → Нативная установка на хосте
```

| Сервис | Установка | Порт | Доступ из Docker |
|--------|-----------|------|------------------|
| **Ollama** | Нативно | 11434 | `host.docker.internal:11434` |
| **Whisper** | Нативно | 9001 | `host.docker.internal:9001` |

---

## 📋 Содержание

1. [Обзор](#обзор)
2. [Требования к Оборудованию](#требования-к-оборудованию)
3. [Установка Ollama](#установка-ollama)
4. [Установка Whisper (faster-whisper-server)](#установка-whisper-faster-whisper-server)
5. [Проверка Работоспособности](#проверка-работоспособности)
6. [Автозапуск](#автозапуск)
7. [Production Конфигурация](#production-конфигурация)
8. [Troubleshooting](#troubleshooting)
9. [Мониторинг](#мониторинг)

---

## 🎯 Обзор

### Почему Нативная Установка?

AI сервисы работают **нативно** на хосте для значительного улучшения производительности:

| Метрика | Docker | Native | Улучшение |
|---------|--------|--------|-----------|
| **Whisper large-v3** | 30-45s | 3-5s | 6-9x |
| **LLM Qwen 2.5 14B** | 60-90s | 5-15s | 4-6x |
| **Full Pipeline** | 90-135s | 8-20s | 5-7x |

### Рекомендуемые Модели

| Задача | Модель | VRAM | Качество |
|--------|--------|------|----------|
| **LLM** | `qwen2.5:14b-instruct-q4_K_M` | 10-12 GB | Отличное понимание команд |
| **STT** | Whisper `large-v3` | 3-4 GB | 98%+ точность для русского |

### Архитектура

```
┌─────────────────────────────────────────────────┐
│                     HOST                         │
├─────────────────────────────────────────────────┤
│  🧠 Ollama (localhost:11434)                    │
│     └── qwen2.5:14b-instruct-q4_K_M             │
│                                                  │
│  🎤 faster-whisper-server (localhost:9001)      │
│     └── large-v3                                 │
└──────────────────┬──────────────────────────────┘
                   │ host.docker.internal
┌──────────────────┴──────────────────────────────┐
│                   DOCKER                         │
├─────────────────────────────────────────────────┤
│  PHP Backend (backend-php83)                    │
│  Centrifugo (backend-centrifugo)                │
│  Redis (backend-redis)                          │
│  PostgreSQL, Nginx, RabbitMQ                    │
└─────────────────────────────────────────────────┘
```

---

## 💻 Требования к Оборудованию

### Development (macOS/Linux)

| Компонент | Минимум | Рекомендуется |
|-----------|---------|---------------|
| **GPU** | - | NVIDIA GPU 8GB+ |
| **RAM** | 16 GB | 32 GB |
| **Storage** | 50 GB SSD | 100 GB NVMe |
| **CPU** | 4 cores | 8+ cores |

### Production Server (RTX 4090)

| Компонент | Спецификация |
|-----------|--------------|
| **GPU** | NVIDIA RTX 4090 24GB VRAM |
| **Bandwidth** | 1008 GB/s |
| **CUDA Cores** | 16,384 |
| **RAM** | 32-64 GB DDR5 |
| **Storage** | 200 GB NVMe SSD |
| **OS** | Ubuntu 22.04 LTS |

**Стоимость**: ~29,000₽/мес (выделенный сервер)

---

## 🧠 Установка Ollama

### macOS

```bash
# Через Homebrew (рекомендуется)
brew install ollama

# Запустить как сервис (автозапуск)
brew services start ollama

# Или запустить вручную
ollama serve
```

### Linux (Ubuntu/Debian)

```bash
# Официальный установщик
curl -fsSL https://ollama.com/install.sh | sh

# Ollama автоматически создаст systemd сервис
# Проверка статуса
sudo systemctl status ollama
```

### Загрузка Модели

```bash
# Production модель (рекомендуется)
ollama pull qwen2.5:14b-instruct-q4_K_M

# Development модель (меньше, быстрее)
ollama pull qwen2.5:7b-instruct-q4_K_M

# Проверка загруженных моделей
ollama list
```

### Конфигурация Ollama

```bash
# Переменные окружения (добавить в ~/.bashrc или ~/.zshrc)
export OLLAMA_NUM_PARALLEL=2        # Параллельные запросы
export OLLAMA_KEEP_ALIVE=24h        # Держать модель в памяти
export OLLAMA_HOST=0.0.0.0:11434    # Слушать на всех интерфейсах
export OLLAMA_MAX_LOADED_MODELS=2   # Максимум моделей в памяти
```

### Проверка Ollama

```bash
# API проверка
curl http://localhost:11434/api/tags

# Тест генерации
curl http://localhost:11434/api/generate -d '{
  "model": "qwen2.5:14b-instruct-q4_K_M",
  "prompt": "Привет! Как дела?",
  "stream": false
}'
```

---

## 🎤 Установка Whisper (faster-whisper-server)

### Требования

- Python 3.9+
- CUDA Toolkit 12.x (для GPU)
- FFmpeg

### Установка FFmpeg

```bash
# macOS
brew install ffmpeg

# Ubuntu/Debian
sudo apt update && sudo apt install -y ffmpeg
```

### Установка faster-whisper-server

**faster-whisper-server** - готовый OpenAI-совместимый сервер для faster-whisper.

```bash
# Создать виртуальное окружение
python3 -m venv ~/whisper-env
source ~/whisper-env/bin/activate

# Установить faster-whisper-server
pip install faster-whisper-server

# Для GPU (NVIDIA)
pip install faster-whisper-server[gpu]
```

### Запуск Сервера

```bash
# Активировать окружение
source ~/whisper-env/bin/activate

# Запустить с моделью large-v3
faster-whisper-server --model large-v3 --host 0.0.0.0 --port 9001

# Или с другими параметрами
faster-whisper-server \
  --model large-v3 \
  --device cuda \
  --compute-type float16 \
  --host 0.0.0.0 \
  --port 9001
```

### Параметры Сервера

| Параметр | Описание | Значение |
|----------|----------|----------|
| `--model` | Модель Whisper | `large-v3` (рекомендуется) |
| `--device` | Устройство | `cuda` или `cpu` |
| `--compute-type` | Точность | `float16` (GPU) / `int8` (CPU) |
| `--host` | Адрес | `0.0.0.0` |
| `--port` | Порт | `9001` |

### Проверка Whisper

```bash
# Health check
curl http://localhost:9001/health

# Или OpenAI-совместимый эндпоинт
curl http://localhost:9001/v1/models
```

---

## ✅ Проверка Работоспособности

### 1. Проверка Нативных Сервисов

```bash
# Ollama
curl http://localhost:11434/api/tags
# Ожидается: JSON с моделями

# Whisper
curl http://localhost:9001/health
# Ожидается: {"status": "ok"} или аналогичный ответ
```

### 2. Проверка из Docker Контейнера

```bash
# Ollama из PHP контейнера
docker exec backend-php83 curl -s http://host.docker.internal:11434/api/tags

# Whisper из PHP контейнера
docker exec backend-php83 curl -s http://host.docker.internal:9001/health
```

### 3. Интеграционный Тест

```bash
#!/bin/bash
# test-ai-integration.sh

echo "=== AI Integration Test ==="

# 1. Проверка Ollama
echo -n "Ollama: "
if curl -s http://localhost:11434/api/tags | grep -q "models"; then
    echo "✅ OK"
else
    echo "❌ FAIL"
    exit 1
fi

# 2. Проверка Whisper
echo -n "Whisper: "
if curl -s http://localhost:9001/health | grep -q -E "(ok|healthy)"; then
    echo "✅ OK"
else
    echo "❌ FAIL"
    exit 1
fi

# 3. Проверка из Docker
echo -n "Docker → Ollama: "
if docker exec backend-php83 curl -s http://host.docker.internal:11434/api/tags | grep -q "models"; then
    echo "✅ OK"
else
    echo "❌ FAIL"
    exit 1
fi

echo -n "Docker → Whisper: "
if docker exec backend-php83 curl -s http://host.docker.internal:9001/health | grep -q -E "(ok|healthy)"; then
    echo "✅ OK"
else
    echo "❌ FAIL"
    exit 1
fi

echo ""
echo "=== All tests passed! ==="
```

---

## 🔄 Автозапуск

### macOS (LaunchAgent)

#### Ollama (если установлен через Homebrew)

```bash
# Автозапуск через Homebrew
brew services start ollama

# Проверка
brew services list | grep ollama
```

#### Whisper LaunchAgent

Файл: `~/Library/LaunchAgents/com.whisper-server.plist`

```xml
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
    <key>Label</key>
    <string>com.whisper-server</string>
    <key>ProgramArguments</key>
    <array>
        <string>/Users/YOUR_USERNAME/whisper-env/bin/faster-whisper-server</string>
        <string>--model</string>
        <string>large-v3</string>
        <string>--host</string>
        <string>0.0.0.0</string>
        <string>--port</string>
        <string>9001</string>
    </array>
    <key>RunAtLoad</key>
    <true/>
    <key>KeepAlive</key>
    <true/>
    <key>StandardOutPath</key>
    <string>/tmp/whisper-server.log</string>
    <key>StandardErrorPath</key>
    <string>/tmp/whisper-server.error.log</string>
</dict>
</plist>
```

**Активация:**

```bash
# Загрузить сервис
launchctl load ~/Library/LaunchAgents/com.whisper-server.plist

# Проверить
launchctl list | grep whisper
```

### Linux (SystemD)

#### Ollama (автоматически создается)

```bash
# Ollama создает сервис автоматически
sudo systemctl enable ollama
sudo systemctl start ollama
sudo systemctl status ollama
```

#### Whisper SystemD Service

Файл: `/etc/systemd/system/whisper-server.service`

```ini
[Unit]
Description=Faster Whisper Server
After=network.target

[Service]
Type=simple
User=YOUR_USERNAME
WorkingDirectory=/home/YOUR_USERNAME
Environment="PATH=/home/YOUR_USERNAME/whisper-env/bin"
ExecStart=/home/YOUR_USERNAME/whisper-env/bin/faster-whisper-server \
    --model large-v3 \
    --device cuda \
    --compute-type float16 \
    --host 0.0.0.0 \
    --port 9001
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

**Активация:**

```bash
sudo systemctl daemon-reload
sudo systemctl enable whisper-server
sudo systemctl start whisper-server
sudo systemctl status whisper-server
```

---

## 🚀 Production Конфигурация

### Ollama Production Override

Файл: `/etc/systemd/system/ollama.service.d/override.conf`

```ini
[Service]
Environment="OLLAMA_NUM_PARALLEL=4"
Environment="OLLAMA_KEEP_ALIVE=24h"
Environment="OLLAMA_HOST=0.0.0.0:11434"
Environment="OLLAMA_MAX_LOADED_MODELS=2"
Environment="CUDA_VISIBLE_DEVICES=0"
```

**Применение:**

```bash
sudo systemctl daemon-reload
sudo systemctl restart ollama
```

### Whisper Production Service

Файл: `/etc/systemd/system/whisper-server.service`

```ini
[Unit]
Description=Faster Whisper Server (Production)
After=network.target nvidia-persistenced.service
Wants=nvidia-persistenced.service

[Service]
Type=simple
User=ai-services
Group=ai-services
WorkingDirectory=/opt/whisper
Environment="PATH=/opt/whisper/venv/bin"
Environment="CUDA_VISIBLE_DEVICES=0"
ExecStart=/opt/whisper/venv/bin/faster-whisper-server \
    --model large-v3 \
    --device cuda \
    --compute-type float16 \
    --host 0.0.0.0 \
    --port 9001
Restart=always
RestartSec=10
LimitNOFILE=65535
LimitNPROC=65535

[Install]
WantedBy=multi-user.target
```

### Проверка GPU Использования

```bash
# NVIDIA GPU мониторинг
nvidia-smi

# Постоянный мониторинг (каждые 2 секунды)
watch -n 2 nvidia-smi

# Проверка CUDA
python3 -c "import torch; print(torch.cuda.is_available())"
```

---

## 🔧 Troubleshooting

### Ollama Проблемы

#### Ollama не запускается

```bash
# Проверить логи
journalctl -u ollama -f           # Linux
cat ~/.ollama/logs/server.log     # macOS

# Проверить порт
lsof -i :11434

# Убить процесс если занят
kill $(lsof -t -i :11434)
```

#### "Model not found"

```bash
# Проверить модели
ollama list

# Загрузить заново
ollama pull qwen2.5:14b-instruct-q4_K_M
```

#### Медленная генерация

```bash
# Проверить GPU использование
nvidia-smi

# Если GPU не используется, проверить CUDA
ollama --version
```

### Whisper Проблемы

#### Whisper не запускается

```bash
# Проверить логи
journalctl -u whisper-server -f   # Linux
tail -f /tmp/whisper-server.log   # macOS

# Проверить порт
lsof -i :9001
```

#### CUDA ошибки

```bash
# Проверить CUDA
nvidia-smi
python3 -c "import torch; print(torch.cuda.is_available())"

# Переустановить с GPU поддержкой
pip install --upgrade faster-whisper-server[gpu]
```

#### Недостаточно VRAM

```bash
# Использовать меньшую модель
faster-whisper-server --model medium --device cuda ...

# Или использовать CPU
faster-whisper-server --model large-v3 --device cpu --compute-type int8 ...
```

### Docker не видит нативные сервисы

```bash
# Проверить host.docker.internal
docker exec backend-php83 ping -c 1 host.docker.internal

# Для Linux: добавить extra_hosts в docker-compose.yml
# services:
#   php83-fpm:
#     extra_hosts:
#       - "host.docker.internal:host-gateway"

# Проверить после добавления
docker-compose down && docker-compose up -d
docker exec backend-php83 curl http://host.docker.internal:11434/api/tags
```

---

## 📊 Мониторинг

### Ollama Мониторинг

```bash
# Список моделей
ollama list

# Запущенные модели
ollama ps

# Остановить все модели
ollama stop $(ollama ps -q)

# Версия
ollama --version
```

### Whisper Мониторинг

```bash
# Проверить процесс
ps aux | grep faster-whisper

# SystemD статус (Linux)
sudo systemctl status whisper-server

# Логи
journalctl -u whisper-server --since "1 hour ago"
```

### GPU Мониторинг

```bash
# Текущее состояние
nvidia-smi

# Постоянный мониторинг
watch -n 1 nvidia-smi

# Детальная информация
nvidia-smi -q
```

### Health Check Script

```bash
#!/bin/bash
# health-check.sh

check_service() {
    local name=$1
    local url=$2
    local response=$(curl -s -o /dev/null -w "%{http_code}" "$url" 2>/dev/null)

    if [ "$response" = "200" ]; then
        echo "✅ $name: OK"
        return 0
    else
        echo "❌ $name: FAIL (HTTP $response)"
        return 1
    fi
}

echo "=== AI Services Health Check ==="
echo ""

check_service "Ollama" "http://localhost:11434/api/tags"
check_service "Whisper" "http://localhost:9001/health"

echo ""
echo "=== GPU Status ==="
nvidia-smi --query-gpu=name,memory.used,memory.total,utilization.gpu --format=csv
```

---

## 🔗 Связанные Документы

- [INDEX.md](INDEX.md) - Главный индекс AI документации
- [01_SETUP.md](01_INFRASTRUCTURE/01_SETUP.md) - Настройка инфраструктуры
- [03_AI_SERVICES.md](01_INFRASTRUCTURE/03_AI_SERVICES.md) - Детальная документация AI сервисов
- [VOICE_AI_ASSISTANT_PLAN.md](../guides/voice-ai/VOICE_AI_ASSISTANT_PLAN.md) - План голосового ассистента

---

**Автор**: Claude Code AI
**Дата создания**: 2025-11-20
**Последнее обновление**: 2025-11-27
