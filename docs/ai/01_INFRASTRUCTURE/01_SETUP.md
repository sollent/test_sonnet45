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

Ports (must be free):
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

Optimizations for low resources:
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
# File: create-structure.sh

# Set base directory
BASE_DIR="$HOME/voice-ai-services"

# Create directory structure
mkdir -p "$BASE_DIR"/{scripts,configs/{ollama,whisper,centrifugo,nginx},volumes/{ollama-data,whisper-models,audio-uploads},logs/{ollama,whisper,centrifugo}}

# Set permissions
chmod 755 "$BASE_DIR"/scripts
chmod 755 "$BASE_DIR"/volumes/*

echo "✅ Directory structure created at $BASE_DIR"
```

---

## 🚀 Пошаговая Установка

### Шаг 1: Подготовка Системы
### ВАЖНО!!! - МОЖНО ПРОПУСТИТЬ И НЕ ДЕЛАТЬ ЭТОТ ШАГ (Так как у меня Macos и мне скорее всего эти локальные пакеты не нужны - главное что у меня есть DOCKER)
```bash
#!/bin/bash
# Step 1.1: Update system
sudo apt-get update && sudo apt-get upgrade -y

# Step 1.2: Install dependencies
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

# Step 1.3: Install Docker (if not installed)
if ! command -v docker &> /dev/null; then
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
    sudo apt-get update
    sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin
fi

# Step 1.4: Add user to docker group
sudo usermod -aG docker $USER
echo "⚠️  Please log out and back in for docker group changes to take effect"

# Step 1.5: Configure swap (for low memory VPS)
if [ ! -f /swapfile ]; then
    sudo fallocate -l 4G /swapfile
    sudo chmod 600 /swapfile
    sudo mkswap /swapfile
    sudo swapon /swapfile
    echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
fi

# Step 1.6: Optimize system for AI workloads
echo "vm.swappiness=10" | sudo tee -a /etc/sysctl.conf
echo "vm.vfs_cache_pressure=50" | sudo tee -a /etc/sysctl.conf
sudo sysctl -p
```

### Шаг 2: Создание Файлов Конфигурации

#### 2.1 Главный Файл Окружения
```bash
# File: infrastructure/ai-services/.env

# Environment Configuration
ENVIRONMENT=development

# Service Versions
OLLAMA_VERSION=latest
WHISPER_VERSION=1.5.4
CENTRIFUGO_VERSION=v5

# Service Ports
OLLAMA_PORT=11434
WHISPER_PORT=8090
CENTRIFUGO_PORT=8000
CENTRIFUGO_ADMIN_PORT=8001
REDIS_PORT=6379

# Ollama Configuration
OLLAMA_MODEL=llama3.2:3b
OLLAMA_HOST=0.0.0.0
OLLAMA_KEEP_ALIVE=5m
OLLAMA_NUM_PARALLEL=2
OLLAMA_MAX_LOADED_MODELS=1
OLLAMA_MEMORY_LIMIT=3G

# Whisper Configuration
WHISPER_MODEL=base
WHISPER_LANGUAGE=ru
WHISPER_THREADS=4
WHISPER_MAX_DURATION=30
WHISPER_MEMORY_LIMIT=1G

# Centrifugo Configuration
CENTRIFUGO_TOKEN_HMAC_SECRET=your-secret-key-min-32-chars-long
CENTRIFUGO_API_KEY=your-api-key-min-32-chars-long
CENTRIFUGO_ADMIN_PASSWORD=your-admin-password
CENTRIFUGO_ADMIN_SECRET=your-admin-secret

# Redis Configuration
REDIS_PASSWORD=your-redis-password
REDIS_MAX_MEMORY=512mb

# Paths
DATA_PATH=./volumes
LOGS_PATH=./logs
CONFIGS_PATH=./configs

# Network
NETWORK_NAME=voice-ai-network
```

#### 2.2 Docker Compose Конфигурация
```yaml
# File: infrastructure/ai-services/docker-compose.yml

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
  # Ollama LLM Service
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

  # Whisper Speech-to-Text Service
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

  # Redis for Centrifugo
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

  # Centrifugo WebSocket Server
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
# File: infrastructure/ai-services/configs/whisper/Dockerfile

FROM python:3.11-slim

ARG WHISPER_VERSION=1.5.4
ARG MODEL_SIZE=base

# Install system dependencies
RUN apt-get update && apt-get install -y \
    ffmpeg \
    git \
    curl \
    build-essential \
    && rm -rf /var/lib/apt/lists/*

# Install Whisper and dependencies
RUN pip install --no-cache-dir \
    openai-whisper==${WHISPER_VERSION} \
    fastapi \
    uvicorn[standard] \
    python-multipart \
    aiofiles \
    prometheus-client

# Create directories
RUN mkdir -p /app /models /uploads /logs

# Copy application code
COPY app.py /app/app.py

# Download model at build time
RUN python -c "import whisper; whisper.load_model('${MODEL_SIZE}', download_root='/models')"

WORKDIR /app

# Health check endpoint
HEALTHCHECK --interval=30s --timeout=5s --start-period=60s \
    CMD curl -f http://localhost:8090/health || exit 1

# Run the application
CMD ["uvicorn", "app:app", "--host", "0.0.0.0", "--port", "8090", "--workers", "2"]
```

#### 2.4 Whisper API Приложение
```python
# File: infrastructure/ai-services/configs/whisper/app.py

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

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('/logs/whisper.log'),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)

# Metrics
transcription_counter = Counter('whisper_transcriptions_total', 'Total transcriptions')
transcription_duration = Histogram('whisper_transcription_duration_seconds', 'Transcription duration')
transcription_errors = Counter('whisper_transcription_errors_total', 'Transcription errors')

# Initialize FastAPI
app = FastAPI(title="Whisper STT API", version="1.0.0")

# Load Whisper model
MODEL_SIZE = os.getenv("MODEL_SIZE", "base")
LANGUAGE = os.getenv("LANGUAGE", "ru")
MAX_DURATION = int(os.getenv("MAX_DURATION", "30"))

logger.info(f"Loading Whisper model: {MODEL_SIZE}")
model = whisper.load_model(MODEL_SIZE, download_root="/models")
logger.info("Model loaded successfully")

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
    """Health check endpoint"""
    return {
        "status": "healthy",
        "model": MODEL_SIZE,
        "language": LANGUAGE,
        "max_duration": MAX_DURATION
    }

@app.get("/metrics")
async def metrics():
    """Prometheus metrics endpoint"""
    return generate_latest()

@app.post("/transcribe", response_model=TranscriptionResponse)
async def transcribe_audio(
    file: UploadFile = File(...),
    language: Optional[str] = LANGUAGE,
    include_segments: bool = False
):
    """
    Transcribe audio file to text

    Args:
        file: Audio file (WAV, MP3, M4A, etc.)
        language: Language code (default: ru)
        include_segments: Include word-level segments

    Returns:
        Transcription result with text and metadata
    """
    start_time = time.time()

    try:
        # Validate file
        if not file.filename:
            raise HTTPException(status_code=400, detail="No file provided")

        # Check file size (max 10MB)
        contents = await file.read()
        if len(contents) > 10 * 1024 * 1024:
            raise HTTPException(status_code=413, detail="File too large (max 10MB)")

        # Save to temporary file
        with tempfile.NamedTemporaryFile(delete=False, suffix=Path(file.filename).suffix) as tmp_file:
            tmp_file.write(contents)
            tmp_path = tmp_file.name

        try:
            # Load audio and check duration
            audio = whisper.load_audio(tmp_path)
            duration = len(audio) / whisper.audio.SAMPLE_RATE

            if duration > MAX_DURATION:
                raise HTTPException(
                    status_code=400,
                    detail=f"Audio too long ({duration:.1f}s). Maximum {MAX_DURATION}s"
                )

            # Transcribe
            logger.info(f"Transcribing {file.filename} ({duration:.1f}s)")

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

            logger.info(f"Transcription completed in {processing_time:.2f}s")

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
            # Clean up temp file
            os.unlink(tmp_path)

    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Transcription error: {str(e)}")
        transcription_errors.inc()
        raise HTTPException(status_code=500, detail=f"Transcription failed: {str(e)}")

@app.post("/transcribe/batch")
async def transcribe_batch(
    files: list[UploadFile] = File(...),
    language: Optional[str] = LANGUAGE,
    background_tasks: BackgroundTasks = BackgroundTasks()
):
    """Batch transcription endpoint"""
    # Implementation for batch processing
    pass

if __name__ == "__main__":
    uvicorn.run(app, host="0.0.0.0", port=8090)
```

#### 2.5 Centrifugo Конфигурация
```json
// File: infrastructure/ai-services/configs/centrifugo/config.json
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
# File: infrastructure/ai-services/scripts/install.sh

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Functions
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check prerequisites
check_prerequisites() {
    log_info "Checking prerequisites..."

    # Check Docker
    if ! command -v docker &> /dev/null; then
        log_error "Docker is not installed"
        exit 1
    fi

    # Check Docker Compose
    if ! command -v docker-compose &> /dev/null; then
        log_error "Docker Compose is not installed"
        exit 1
    fi

    # Check ports
    for port in 11434 8090 8000 8001 6379; do
        if lsof -Pi :$port -sTCP:LISTEN -t >/dev/null ; then
            log_warn "Port $port is already in use"
        fi
    done

    log_info "Prerequisites check completed"
}

# Load environment
load_environment() {
    if [ -f .env ]; then
        export $(cat .env | grep -v '^#' | xargs)
        log_info "Environment loaded"
    else
        log_error ".env file not found"
        exit 1
    fi
}

# Build Whisper image
build_whisper() {
    log_info "Building Whisper Docker image..."
    docker build -t voice-ai/whisper:${WHISPER_VERSION} \
        --build-arg WHISPER_VERSION=${WHISPER_VERSION} \
        --build-arg MODEL_SIZE=${WHISPER_MODEL} \
        ${CONFIGS_PATH}/whisper
}

# Start services
start_services() {
    log_info "Starting AI services..."
    docker-compose up -d

    log_info "Waiting for services to be healthy..."
    sleep 10

    # Check health
    docker-compose ps
}

# Load Ollama model
load_ollama_model() {
    log_info "Loading Ollama model: ${OLLAMA_MODEL}..."

    # Wait for Ollama to be ready
    while ! curl -s http://localhost:${OLLAMA_PORT}/api/tags > /dev/null 2>&1; do
        log_info "Waiting for Ollama to start..."
        sleep 5
    done

    # Pull the model
    docker exec voice-ai-ollama ollama pull ${OLLAMA_MODEL}

    log_info "Model loaded successfully"
}

# Main installation
main() {
    log_info "Starting Voice AI Services installation..."

    check_prerequisites
    load_environment
    build_whisper
    start_services
    load_ollama_model

    log_info "Installation completed successfully!"
    log_info "Access points:"
    log_info "  - Ollama API: http://localhost:${OLLAMA_PORT}"
    log_info "  - Whisper API: http://localhost:${WHISPER_PORT}"
    log_info "  - Centrifugo: http://localhost:${CENTRIFUGO_PORT}"
    log_info "  - Centrifugo Admin: http://localhost:${CENTRIFUGO_ADMIN_PORT}"
}

# Run main function
main "$@"
```

#### 3.2 Скрипт Проверки Здоровья
```bash
#!/bin/bash
# File: infrastructure/ai-services/scripts/health-check.sh

set -e

# Load environment
source .env

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Health check function
check_service() {
    local service_name=$1
    local url=$2

    if curl -s -o /dev/null -w "%{http_code}" "$url" | grep -q "200\|204"; then
        echo -e "${GREEN}✓${NC} $service_name is healthy"
        return 0
    else
        echo -e "${RED}✗${NC} $service_name is not responding"
        return 1
    fi
}

# Main checks
echo "=== Voice AI Services Health Check ==="
echo ""

# Check Docker containers
echo "Docker Containers:"
docker-compose ps

echo ""
echo "Service Health:"

# Check each service
check_service "Ollama" "http://localhost:${OLLAMA_PORT}/api/tags"
check_service "Whisper" "http://localhost:${WHISPER_PORT}/health"
check_service "Centrifugo" "http://localhost:${CENTRIFUGO_PORT}/health"

# Check Redis
if docker exec voice-ai-redis redis-cli -a ${REDIS_PASSWORD} ping > /dev/null 2>&1; then
    echo -e "${GREEN}✓${NC} Redis is healthy"
else
    echo -e "${RED}✗${NC} Redis is not responding"
fi

# Check resources
echo ""
echo "Resource Usage:"
docker stats --no-stream voice-ai-ollama voice-ai-whisper voice-ai-centrifugo voice-ai-redis

# Test Ollama
echo ""
echo "Testing Ollama..."
curl -s -X POST http://localhost:${OLLAMA_PORT}/api/generate \
    -H "Content-Type: application/json" \
    -d '{
        "model": "'${OLLAMA_MODEL}'",
        "prompt": "Hello",
        "stream": false,
        "options": {
            "num_predict": 10
        }
    }' | jq -r '.response' || echo "Ollama test failed"

echo ""
echo "=== Health check completed ==="
```

---

## ✅ Проверка и Тестирование

### Чеклист Проверки

```bash
# 1. Проверить что все контейнеры запущены
docker-compose ps

# Expected output:
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

# Kill process
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
