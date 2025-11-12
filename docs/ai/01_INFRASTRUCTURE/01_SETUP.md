# 📦 Фаза 1.1: Руководство по Настройке Инфраструктуры

> **Версия Документа**: 1.0.0
> **Последнее Обновление**: 2025-11-08
> **Предполагаемое Время**: 2 дня
> **Сложность**: ВЫСОКАЯ
> **Предварительные Требования**: Знание администрирования Linux, опыт работы с Docker

## 📋 Содержание

1. [Системные Требования](#системные-требования)
2. [Структура Директорий](#структура-директорий)
3. [Пошаговая Установка](#пошаговая-установка)
4. [Файлы Конфигурации](#файлы-конфигурации)
5. [Проверка и Тестирование](#проверка-и-тестирование)
6. [Устранение Неполадок](#устранение-неполадок)

---

## 🖥️ Системные Требования

### Минимальные Требования (Разработка)

```yaml
Hardware:
  CPU: 4 ядра @ 2.4GHz
  RAM: 8GB
  Storage: 60GB SSD
  Network: 100 Mbps

Software:
  OS: Ubuntu 22.04 LTS / Debian 12
  Docker: 24.0.0+
  Docker Compose: 2.20.0+
  Git: 2.34+

Ports (должны быть свободны):
  - 80, 443: Nginx
  - 3000: Frontend dev server
  - 8089: Backend API
  - 8000: Centrifugo WebSocket
  - 8001: Centrifugo Admin
  - 8090: Whisper API
  - 11434: Ollama API
  - 5432: PostgreSQL
  - 5672: RabbitMQ
  - 15672: RabbitMQ Management
  - 6379: Redis
```

### Требования для Продакшна (VPS)

```yaml
Hardware:
  CPU: 2 ядра @ 5.0GHz (как указано)
  RAM: 4GB
  Storage: 40GB SSD
  Network: 100 Mbps

Оптимизация для низких ресурсов:
  - Используйте Llama 3.2 1B вместо 3B
  - Whisper tiny модель вместо base
  - Отключите ненужные сервисы
  - Настройка swap файла
```

---

## 📁 Структура Директорий

### Полная Структура Проекта

```bash
# Структура проекта (MONOREPO)
task-manager/                        # Корень проекта
├── apps/                            # Код приложений
│   ├── backend/                     # Symfony приложение
│   │   ├── src/                     # PHP исходный код
│   │   ├── config/                  # Файлы конфигурации
│   │   ├── migrations/              # Миграции базы данных
│   │   ├── tests/                   # PHPUnit тесты
│   │   └── ...
│   └── frontend/                    # Vue.js приложение
│       ├── src/                     # TypeScript исходный код
│       ├── e2e/                     # E2E тесты (Playwright)
│       ├── public/                  # Статические ассеты
│       └── ...
├── infrastructure/                  # Конфигурации инфраструктуры
│   ├── docker/                      # Docker конфигурации
│   │   ├── docker-compose.app.yml   # Сервисы приложения
│   │   ├── docker-compose.ai.yml    # AI сервисы (placeholder)
│   │   ├── docker-compose.dev.yml   # Dev overrides
│   │   ├── dev/                     # Dev configs
│   │   │   ├── nginx/               # Nginx configs
│   │   │   └── php/                 # PHP-FPM configs
│   │   └── cron/                    # Cron jobs
│   └── ai-services/                 # AI инфраструктура (будущее)
│       ├── configs/                 # Конфигурации AI сервисов (placeholders)
│       │   ├── ollama/              # LLM configs
│       │   ├── whisper/             # STT configs
│       │   └── centrifugo/          # WebSocket configs
│       └── scripts/                 # Скрипты настройки AI (placeholders)
├── scripts/                         # Утилиты (общие для проекта)
│   ├── setup-dev.sh                 # Настройка разработки
│   ├── reset-db.sh                  # Сброс базы данных
│   └── health-check.sh              # Проверка здоровья
├── docs/                            # Документация
│   ├── ai/                          # Voice AI документация
│   │   ├── 01_INFRASTRUCTURE/
│   │   ├── 02_BACKEND/
│   │   ├── 03_FRONTEND/
│   │   └── REFERENCE/
│   ├── backend/                     # Backend документация
│   ├── frontend/                    # Frontend документация
│   └── guides/                      # Руководства по разработке
├── docker-compose.yml               # Главный compose (включает все)
├── Makefile                         # Общие команды
├── CLAUDE.md                        # Краткий справочник для AI
└── RESTRUCTURE_PROJECT.md           # План реструктуризации
```

**Примечание**: Вся инфраструктура (включая будущие AI сервисы) содержится в этом монорепозитории. Директория `infrastructure/ai-services/` содержит placeholders и будет реализована во время фазы разработки Voice AI.

### Скрипт Создания Структуры Директорий

```bash
#!/bin/bash
# Файл: create-structure.sh

# Установить базовую директорию
BASE_DIR="$HOME/voice-ai-services"

# Создать структуру директорий
mkdir -p "$BASE_DIR"/{scripts,configs/{ollama,whisper,centrifugo,nginx},volumes/{ollama-data,whisper-models,audio-uploads},logs/{ollama,whisper,centrifugo}}

# Установить разрешения
chmod 755 "$BASE_DIR"/scripts
chmod 755 "$BASE_DIR"/volumes/*

echo "✅ Структура директорий создана в $BASE_DIR"
```

---

## 🚀 Пошаговая Установка

### Шаг 1: Подготовка Системы
### ВАЖНО!!! - МОЖНО ПРОПУСТИТЬ И НЕ ДЕЛАТЬ ЭТОТ ШАГ (Так как у меня Macos и мне скорее всего эти локальные пакеты не нужны - главное что у меня есть DOCKER)
```bash
#!/bin/bash
# Шаг 1.1: Обновить систему
sudo apt-get update && sudo apt-get upgrade -y

# Шаг 1.2: Установить зависимости
sudo apt-get install -y \
    curl \
    wget \
    git \
    htop \
    net-tools \
    ca-certificates \
    gnupg \
    lsb-release \
    python3-pip \
    ffmpeg

# Шаг 1.3: Установить Docker (если не установлен)
if ! command -v docker &> /dev/null; then
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
    sudo apt-get update
    sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin
fi

# Шаг 1.4: Добавить пользователя в группу docker
sudo usermod -aG docker $USER
echo "⚠️  Пожалуйста, выйдите и войдите снова, чтобы изменения группы docker вступили в силу"

# Шаг 1.5: Настроить swap (для VPS с низкой памятью)
if [ ! -f /swapfile ]; then
    sudo fallocate -l 4G /swapfile
    sudo chmod 600 /swapfile
    sudo mkswap /swapfile
    sudo swapon /swapfile
    echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
fi

# Шаг 1.6: Оптимизировать систему для AI рабочих нагрузок
echo "vm.swappiness=10" | sudo tee -a /etc/sysctl.conf
echo "vm.vfs_cache_pressure=50" | sudo tee -a /etc/sysctl.conf
sudo sysctl -p
```

### Шаг 2: Создание Файлов Конфигурации

#### 2.1 Главный Файл Окружения
```bash
# Файл: infrastructure/ai-services/.env

# Конфигурация Окружения
ENVIRONMENT=development

# Версии Сервисов
OLLAMA_VERSION=latest
WHISPER_VERSION=1.5.4
CENTRIFUGO_VERSION=v5

# Порты Сервисов
OLLAMA_PORT=11434
WHISPER_PORT=8090
CENTRIFUGO_PORT=8000
CENTRIFUGO_ADMIN_PORT=8001
REDIS_PORT=6379

# Конфигурация Ollama
OLLAMA_MODEL=llama3.2:3b
OLLAMA_HOST=0.0.0.0
OLLAMA_KEEP_ALIVE=5m
OLLAMA_NUM_PARALLEL=2
OLLAMA_MAX_LOADED_MODELS=1
OLLAMA_MEMORY_LIMIT=3G

# Конфигурация Whisper
WHISPER_MODEL=base
WHISPER_LANGUAGE=ru
WHISPER_THREADS=4
WHISPER_MAX_DURATION=30
WHISPER_MEMORY_LIMIT=1G

# Конфигурация Centrifugo
CENTRIFUGO_TOKEN_HMAC_SECRET=your-secret-key-min-32-chars-long
CENTRIFUGO_API_KEY=your-api-key-min-32-chars-long
CENTRIFUGO_ADMIN_PASSWORD=your-admin-password
CENTRIFUGO_ADMIN_SECRET=your-admin-secret

# Конфигурация Redis
REDIS_PASSWORD=your-redis-password
REDIS_MAX_MEMORY=512mb

# Пути
DATA_PATH=./volumes
LOGS_PATH=./logs
CONFIGS_PATH=./configs

# Сеть
NETWORK_NAME=voice-ai-network
```

#### 2.2 Docker Compose Конфигурация
```yaml
# Файл: infrastructure/ai-services/docker-compose.yml

version: '3.8'

networks:
  voice-ai-network:
    driver: bridge
    ipam:
      config:
        - subnet: 172.20.0.0/16

volumes:
  ollama-data:
  whisper-models:
  audio-uploads:
  redis-data:

services:
  # Сервис Ollama LLM
  ollama:
    image: ollama/ollama:${OLLAMA_VERSION}
    container_name: voice-ai-ollama
    restart: unless-stopped
    ports:
      - "${OLLAMA_PORT}:11434"
    volumes:
      - ollama-data:/root/.ollama
      - ${CONFIGS_PATH}/ollama:/config
      - ${LOGS_PATH}/ollama:/logs
    environment:
      - OLLAMA_HOST=${OLLAMA_HOST}
      - OLLAMA_KEEP_ALIVE=${OLLAMA_KEEP_ALIVE}
      - OLLAMA_NUM_PARALLEL=${OLLAMA_NUM_PARALLEL}
      - OLLAMA_MAX_LOADED_MODELS=${OLLAMA_MAX_LOADED_MODELS}
    networks:
      voice-ai-network:
        ipv4_address: 172.20.0.10
    deploy:
      resources:
        limits:
          memory: ${OLLAMA_MEMORY_LIMIT}
        reservations:
          memory: 2G
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:11434/api/tags"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 60s

  # Сервис Whisper Speech-to-Text
  whisper:
    build:
      context: ${CONFIGS_PATH}/whisper
      dockerfile: Dockerfile
      args:
        - WHISPER_VERSION=${WHISPER_VERSION}
        - MODEL_SIZE=${WHISPER_MODEL}
    image: voice-ai/whisper:${WHISPER_VERSION}
    container_name: voice-ai-whisper
    restart: unless-stopped
    ports:
      - "${WHISPER_PORT}:8090"
    volumes:
      - whisper-models:/models
      - audio-uploads:/uploads
      - ${LOGS_PATH}/whisper:/logs
    environment:
      - MODEL_SIZE=${WHISPER_MODEL}
      - LANGUAGE=${WHISPER_LANGUAGE}
      - THREADS=${WHISPER_THREADS}
      - MAX_DURATION=${WHISPER_MAX_DURATION}
    networks:
      voice-ai-network:
        ipv4_address: 172.20.0.11
    deploy:
      resources:
        limits:
          memory: ${WHISPER_MEMORY_LIMIT}
        reservations:
          memory: 512M
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:8090/health"]
      interval: 30s
      timeout: 5s
      retries: 3

  # Redis для Centrifugo
  redis:
    image: redis:7.2-alpine
    container_name: voice-ai-redis
    restart: unless-stopped
    ports:
      - "${REDIS_PORT}:6379"
    volumes:
      - redis-data:/data
    environment:
      - REDIS_PASSWORD=${REDIS_PASSWORD}
    command: redis-server --requirepass ${REDIS_PASSWORD} --maxmemory ${REDIS_MAX_MEMORY} --maxmemory-policy allkeys-lru
    networks:
      voice-ai-network:
        ipv4_address: 172.20.0.12
    healthcheck:
      test: ["CMD", "redis-cli", "--raw", "incr", "ping"]
      interval: 30s
      timeout: 3s
      retries: 3

  # Сервер Centrifugo WebSocket
  centrifugo:
    image: centrifugo/centrifugo:${CENTRIFUGO_VERSION}
    container_name: voice-ai-centrifugo
    restart: unless-stopped
    ports:
      - "${CENTRIFUGO_PORT}:8000"
      - "${CENTRIFUGO_ADMIN_PORT}:8001"
    volumes:
      - ${CONFIGS_PATH}/centrifugo:/centrifugo
      - ${LOGS_PATH}/centrifugo:/logs
    command: centrifugo -c /centrifugo/config.json --log_level=info --log_file=/logs/centrifugo.log
    depends_on:
      - redis
    networks:
      voice-ai-network:
        ipv4_address: 172.20.0.13
    healthcheck:
      test: ["CMD", "wget", "--no-verbose", "--tries=1", "--spider", "http://localhost:8000/health"]
      interval: 30s
      timeout: 5s
      retries: 3
```

#### 2.3 Whisper Dockerfile
```dockerfile
# Файл: infrastructure/ai-services/configs/whisper/Dockerfile

FROM python:3.11-slim

ARG WHISPER_VERSION=1.5.4
ARG MODEL_SIZE=base

# Установить системные зависимости
RUN apt-get update && apt-get install -y \
    ffmpeg \
    git \
    curl \
    build-essential \
    && rm -rf /var/lib/apt/lists/*

# Установить Whisper и зависимости
RUN pip install --no-cache-dir \
    openai-whisper==${WHISPER_VERSION} \
    fastapi \
    uvicorn[standard] \
    python-multipart \
    aiofiles \
    prometheus-client

# Создать директории
RUN mkdir -p /app /models /uploads /logs

# Скопировать код приложения
COPY app.py /app/app.py

# Загрузить модель во время сборки
RUN python -c "import whisper; whisper.load_model('${MODEL_SIZE}', download_root='/models')"

WORKDIR /app

# Проверка здоровья
HEALTHCHECK --interval=30s --timeout=5s --start-period=60s \
    CMD curl -f http://localhost:8090/health || exit 1

# Запустить приложение
CMD ["uvicorn", "app:app", "--host", "0.0.0.0", "--port", "8090", "--workers", "2"]
```

#### 2.4 Whisper API Приложение
```python
# Файл: infrastructure/ai-services/configs/whisper/app.py

import os
import time
import logging
import tempfile
import asyncio
from typing import Optional, Dict, Any
from pathlib import Path

import whisper
import uvicorn
from fastapi import FastAPI, File, UploadFile, HTTPException, BackgroundTasks
from fastapi.responses import JSONResponse
from pydantic import BaseModel
from prometheus_client import Counter, Histogram, generate_latest

# Настроить логирование
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('/logs/whisper.log'),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)

# Метрики
transcription_counter = Counter('whisper_transcriptions_total', 'Всего транскрипций')
transcription_duration = Histogram('whisper_transcription_duration_seconds', 'Длительность транскрипции')
transcription_errors = Counter('whisper_transcription_errors_total', 'Ошибки транскрипции')

# Инициализировать FastAPI
app = FastAPI(title="Whisper STT API", version="1.0.0")

# Загрузить модель Whisper
MODEL_SIZE = os.getenv("MODEL_SIZE", "base")
LANGUAGE = os.getenv("LANGUAGE", "ru")
MAX_DURATION = int(os.getenv("MAX_DURATION", "30"))

logger.info(f"Загрузка модели Whisper: {MODEL_SIZE}")
model = whisper.load_model(MODEL_SIZE, download_root="/models")
logger.info("Модель загружена успешно")

class TranscriptionRequest(BaseModel):
    language: Optional[str] = LANGUAGE
    task: Optional[str] = "transcribe"
    temperature: Optional[float] = 0.0

class TranscriptionResponse(BaseModel):
    text: str
    language: str
    duration: float
    processing_time: float
    segments: Optional[list] = None

@app.get("/health")
async def health_check():
    """Конечная точка проверки здоровья"""
    return {
        "status": "healthy",
        "model": MODEL_SIZE,
        "language": LANGUAGE,
        "max_duration": MAX_DURATION
    }

@app.get("/metrics")
async def metrics():
    """Конечная точка метрик Prometheus"""
    return generate_latest()

@app.post("/transcribe", response_model=TranscriptionResponse)
async def transcribe_audio(
    file: UploadFile = File(...),
    language: Optional[str] = LANGUAGE,
    include_segments: bool = False
):
    """
    Транскрибировать аудио файл в текст

    Args:
        file: Аудио файл (WAV, MP3, M4A, и т.д.)
        language: Код языка (по умолчанию: ru)
        include_segments: Включить сегменты на уровне слов

    Returns:
        Результат транскрипции с текстом и метаданными
    """
    start_time = time.time()

    try:
        # Валидация файла
        if not file.filename:
            raise HTTPException(status_code=400, detail="Файл не предоставлен")

        # Проверить размер файла (максимум 10MB)
        contents = await file.read()
        if len(contents) > 10 * 1024 * 1024:
            raise HTTPException(status_code=413, detail="Файл слишком большой (максимум 10MB)")

        # Сохранить во временный файл
        with tempfile.NamedTemporaryFile(delete=False, suffix=Path(file.filename).suffix) as tmp_file:
            tmp_file.write(contents)
            tmp_path = tmp_file.name

        try:
            # Загрузить аудио и проверить длительность
            audio = whisper.load_audio(tmp_path)
            duration = len(audio) / whisper.audio.SAMPLE_RATE

            if duration > MAX_DURATION:
                raise HTTPException(
                    status_code=400,
                    detail=f"Аудио слишком длинное ({duration:.1f}с). Максимум {MAX_DURATION}с"
                )

            # Транскрибировать
            logger.info(f"Транскрибируется {file.filename} ({duration:.1f}с)")

            with transcription_duration.time():
                result = model.transcribe(
                    tmp_path,
                    language=language,
                    temperature=0.0,
                    no_speech_threshold=0.6,
                    logprob_threshold=-1.0,
                    compression_ratio_threshold=2.4,
                    condition_on_previous_text=True,
                    verbose=False
                )

            transcription_counter.inc()
            processing_time = time.time() - start_time

            logger.info(f"Транскрипция завершена за {processing_time:.2f}с")

            response = TranscriptionResponse(
                text=result["text"].strip(),
                language=result["language"],
                duration=duration,
                processing_time=processing_time
            )

            if include_segments:
                response.segments = result["segments"]

            return response

        finally:
            # Очистить временный файл
            os.unlink(tmp_path)

    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Ошибка транскрипции: {str(e)}")
        transcription_errors.inc()
        raise HTTPException(status_code=500, detail=f"Транскрипция не удалась: {str(e)}")

@app.post("/transcribe/batch")
async def transcribe_batch(
    files: list[UploadFile] = File(...),
    language: Optional[str] = LANGUAGE,
    background_tasks: BackgroundTasks = BackgroundTasks()
):
    """Конечная точка пакетной транскрипции"""
    # Реализация пакетной обработки
    pass

if __name__ == "__main__":
    uvicorn.run(app, host="0.0.0.0", port=8090)
```

#### 2.5 Centrifugo Конфигурация
```json
// Файл: infrastructure/ai-services/configs/centrifugo/config.json
{
  "token_hmac_secret_key": "${CENTRIFUGO_TOKEN_HMAC_SECRET}",
  "api_key": "${CENTRIFUGO_API_KEY}",
  "admin": true,
  "admin_password": "${CENTRIFUGO_ADMIN_PASSWORD}",
  "admin_secret": "${CENTRIFUGO_ADMIN_SECRET}",
  "admin_insecure": false,

  "engine": "redis",
  "redis_address": "redis:6379",
  "redis_password": "${REDIS_PASSWORD}",
  "redis_db": 0,

  "allowed_origins": [
    "http://localhost:3000",
    "http://localhost:8089",
    "https://your-domain.com"
  ],

  "namespaces": [
    {
      "name": "voice",
      "presence": true,
      "join_leave": true,
      "history_size": 10,
      "history_ttl": "60s",
      "history_recover": true,
      "allow_publish_for_subscriber": false,
      "allow_subscribe_for_client": false
    },
    {
      "name": "tasks",
      "presence": false,
      "join_leave": false,
      "history_size": 100,
      "history_ttl": "300s",
      "history_recover": true
    }
  ],

  "log_level": "info",
  "log_file": "/logs/centrifugo.log",

  "health": true,
  "prometheus": true,
  "prometheus_port": 9090,

  "client_insecure": false,
  "client_anonymous": false,
  "client_concurrency": 128,

  "channel_max_length": 255,
  "user_connection_limit": 10,
  "user_subscribe_to_personal": true,
  "user_personal_channel_namespace": "personal",

  "presence_ttl": "60s",
  "presence_ping_interval": "25s",
  "presence_pong_timeout": "10s",

  "client_stale_close_delay": "10s",
  "client_expired_close_delay": "10s",
  "client_expired_sub_close_delay": "10s",

  "websocket_compression": true,
  "websocket_compression_level": 1,
  "websocket_read_buffer_size": 1024,
  "websocket_write_buffer_size": 1024,
  "websocket_message_size_limit": 65536
}
```

### Шаг 3: Скрипты Установки

#### 3.1 Главный Скрипт Установки
```bash
#!/bin/bash
# Файл: infrastructure/ai-services/scripts/install.sh

set -e

# Цвета для вывода
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # Без цвета

# Функции
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Проверка предварительных требований
check_prerequisites() {
    log_info "Проверка предварительных требований..."

    # Проверка Docker
    if ! command -v docker &> /dev/null; then
        log_error "Docker не установлен"
        exit 1
    fi

    # Проверка Docker Compose
    if ! command -v docker-compose &> /dev/null; then
        log_error "Docker Compose не установлен"
        exit 1
    fi

    # Проверка портов
    for port in 11434 8090 8000 8001 6379; do
        if lsof -Pi :$port -sTCP:LISTEN -t >/dev/null ; then
            log_warn "Порт $port уже используется"
        fi
    done

    log_info "Проверка предварительных требований завершена"
}

# Загрузка окружения
load_environment() {
    if [ -f .env ]; then
        export $(cat .env | grep -v '^#' | xargs)
        log_info "Окружение загружено"
    else
        log_error "Файл .env не найден"
        exit 1
    fi
}

# Сборка образа Whisper
build_whisper() {
    log_info "Сборка Docker образа Whisper..."
    docker build -t voice-ai/whisper:${WHISPER_VERSION} \
        --build-arg WHISPER_VERSION=${WHISPER_VERSION} \
        --build-arg MODEL_SIZE=${WHISPER_MODEL} \
        ${CONFIGS_PATH}/whisper
}

# Запуск сервисов
start_services() {
    log_info "Запуск AI сервисов..."
    docker-compose up -d

    log_info "Ожидание готовности сервисов..."
    sleep 10

    # Проверка здоровья
    docker-compose ps
}

# Загрузка модели Ollama
load_ollama_model() {
    log_info "Загрузка модели Ollama: ${OLLAMA_MODEL}..."

    # Ожидание готовности Ollama
    while ! curl -s http://localhost:${OLLAMA_PORT}/api/tags > /dev/null 2>&1; do
        log_info "Ожидание запуска Ollama..."
        sleep 5
    done

    # Загрузка модели
    docker exec voice-ai-ollama ollama pull ${OLLAMA_MODEL}

    log_info "Модель загружена успешно"
}

# Главная установка
main() {
    log_info "Начало установки Voice AI Services..."

    check_prerequisites
    load_environment
    build_whisper
    start_services
    load_ollama_model

    log_info "Установка завершена успешно!"
    log_info "Точки доступа:"
    log_info "  - Ollama API: http://localhost:${OLLAMA_PORT}"
    log_info "  - Whisper API: http://localhost:${WHISPER_PORT}"
    log_info "  - Centrifugo: http://localhost:${CENTRIFUGO_PORT}"
    log_info "  - Centrifugo Admin: http://localhost:${CENTRIFUGO_ADMIN_PORT}"
}

# Запуск главной функции
main "$@"
```

#### 3.2 Скрипт Проверки Здоровья
```bash
#!/bin/bash
# Файл: infrastructure/ai-services/scripts/health-check.sh

set -e

# Загрузка окружения
source .env

# Цвета
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Функция проверки здоровья
check_service() {
    local service_name=$1
    local url=$2

    if curl -s -o /dev/null -w "%{http_code}" "$url" | grep -q "200\|204"; then
        echo -e "${GREEN}✓${NC} $service_name здоров"
        return 0
    else
        echo -e "${RED}✗${NC} $service_name не отвечает"
        return 1
    fi
}

# Главные проверки
echo "=== Проверка Здоровья Voice AI Services ==="
echo ""

# Проверка контейнеров Docker
echo "Контейнеры Docker:"
docker-compose ps

echo ""
echo "Здоровье Сервисов:"

# Проверка каждого сервиса
check_service "Ollama" "http://localhost:${OLLAMA_PORT}/api/tags"
check_service "Whisper" "http://localhost:${WHISPER_PORT}/health"
check_service "Centrifugo" "http://localhost:${CENTRIFUGO_PORT}/health"

# Проверка Redis
if docker exec voice-ai-redis redis-cli -a ${REDIS_PASSWORD} ping > /dev/null 2>&1; then
    echo -e "${GREEN}✓${NC} Redis здоров"
else
    echo -e "${RED}✗${NC} Redis не отвечает"
fi

# Проверка ресурсов
echo ""
echo "Использование Ресурсов:"
docker stats --no-stream voice-ai-ollama voice-ai-whisper voice-ai-centrifugo voice-ai-redis

# Тест Ollama
echo ""
echo "Тестирование Ollama..."
curl -s -X POST http://localhost:${OLLAMA_PORT}/api/generate \
    -H "Content-Type: application/json" \
    -d '{
        "model": "'${OLLAMA_MODEL}'",
        "prompt": "Hello",
        "stream": false,
        "options": {
            "num_predict": 10
        }
    }' | jq -r '.response' || echo "Тест Ollama не удался"

echo ""
echo "=== Проверка здоровья завершена ==="
```

---

## ✅ Проверка и Тестирование

### Чеклист Проверки

```bash
# 1. Проверить что все контейнеры запущены
docker-compose ps

# Ожидаемый вывод:
# NAME                 STATUS              PORTS
# voice-ai-ollama      running (healthy)   0.0.0.0:11434->11434/tcp
# voice-ai-whisper     running (healthy)   0.0.0.0:8090->8090/tcp
# voice-ai-centrifugo  running (healthy)   0.0.0.0:8000->8000/tcp
# voice-ai-redis       running (healthy)   0.0.0.0:6379->6379/tcp

# 2. Тест Ollama API
curl http://localhost:11434/api/tags

# 3. Тест Whisper API
curl http://localhost:8090/health

# 4. Тест Centrifugo
curl http://localhost:8000/health

# 5. Тест Redis
docker exec voice-ai-redis redis-cli -a your-redis-password ping

# 6. Проверить логи на ошибки
docker-compose logs --tail=50

# 7. Тест инференса модели
curl -X POST http://localhost:11434/api/generate \
  -H "Content-Type: application/json" \
  -d '{
    "model": "llama3.2:3b",
    "prompt": "Создай задачу",
    "stream": false
  }'
```

---

## 🔧 Устранение Неполадок

### Общие Проблемы и Решения

#### Проблема 1: Нехватка Памяти
```bash
# Симптом: Контейнер постоянно перезапускается
# Решение: Уменьшить размер модели или добавить swap

# Проверить использование памяти
free -h
docker stats

# Увеличить swap
sudo fallocate -l 8G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile

# Использовать меньшую модель
# Изменить в .env: OLLAMA_MODEL=llama3.2:1b
```

#### Проблема 2: Порт Уже Занят
```bash
# Найти процесс использующий порт
sudo lsof -i :11434

# Убить процесс
sudo kill -9 <PID>

# Или изменить порт в .env
```

#### Проблема 3: Загрузка Модели Не Удалась
```bash
# Ручная загрузка
docker exec -it voice-ai-ollama bash
ollama pull llama3.2:3b

# Проверить место на диске
df -h

# Очистить кеш Docker
docker system prune -a
```

#### Проблема 4: Сборка Whisper Не Удалась
```bash
# Сборка без кеша
docker build --no-cache -t voice-ai/whisper:1.5.4 configs/whisper/

# Проверить логи сборки
docker build -t voice-ai/whisper:1.5.4 configs/whisper/ 2>&1 | tee build.log
```

---

## 📊 Мониторинг Производительности

```bash
# Мониторинг в реальном времени
htop

# Использование ресурсов Docker
docker stats

# Логи сервисов
tail -f logs/ollama/ollama.log
tail -f logs/whisper/whisper.log
tail -f logs/centrifugo/centrifugo.log

# Мониторинг сети
netstat -tulpn | grep LISTEN

# Использование диска
du -sh volumes/*
```

---

## 🔐 Укрепление Безопасности

```bash
# 1. Настройка firewall
sudo ufw allow 22/tcp  # SSH
sudo ufw allow 80/tcp  # HTTP
sudo ufw allow 443/tcp # HTTPS
sudo ufw enable

# 2. Защита файла окружения
chmod 600 .env

# 3. Регулярные обновления
sudo apt-get update && sudo apt-get upgrade -y
docker-compose pull

# 4. Резервное копирование конфигурации
./scripts/backup.sh
```

---

## ✅ Следующие Шаги

1. ✅ Инфраструктура настроена и работает
2. → Перейти к [Docker Конфигурации](02_DOCKER.md)
3. → Затем [Установка AI Сервисов](03_AI_SERVICES.md)
4. → Наконец [Безопасность и Сеть](04_SECURITY.md)

---

**Статус Документа**: Завершен
**Последнее Тестирование**: 2025-11-08
**Автор**: AI Architecture Team
