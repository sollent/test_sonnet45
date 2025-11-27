# 🤖 Фаза 1.3: Установка и Конфигурация AI Сервисов

> **Версия Документа**: 2.0.0
> **Последнее Обновление**: 2025-11-27
> **Предполагаемое Время**: 1 день
> **Сложность**: СРЕДНЯЯ
> **Предварительные Требования**: Нативная установка (не Docker!)

---

## ⚠️ КРИТИЧЕСКИ ВАЖНО: Нативная Установка

**AI сервисы (Ollama и Whisper) работают НАТИВНО на хосте, НЕ в Docker!**

Это обеспечивает **10-20x улучшение производительности**:
- Whisper: 0.5-1s (вместо 10-15s в Docker)
- LLM: 3-5s (вместо 60-90s в Docker)

**Полное руководство по установке**: [`NATIVE_INSTALLATION.md`](../NATIVE_INSTALLATION.md)

---

## 📋 Содержание

1. [Обзор Архитектуры](#обзор-архитектуры)
2. [Ollama (LLM)](#ollama-llm)
3. [Whisper (STT)](#whisper-stt)
4. [Centrifugo (WebSocket)](#centrifugo-websocket)
5. [Интеграционное Тестирование](#интеграционное-тестирование)
6. [Production Конфигурация](#production-конфигурация)
7. [Устранение Неполадок](#устранение-неполадок)

---

## 🏗️ Обзор Архитектуры

### Компоненты

| Сервис | Установка | Порт | Назначение |
|--------|-----------|------|------------|
| **Ollama** | Нативная | 11434 | LLM inference (Qwen 2.5) |
| **Whisper** | Нативная | 9001 | Speech-to-Text (faster-whisper) |
| **Centrifugo** | Docker | 8000 | WebSocket real-time updates |
| **Redis** | Docker | 6379 | Cache, pub/sub для Centrifugo |

### Схема Взаимодействия

```
┌─────────────────────────────────────────────────────────────┐
│                      HOST (Нативно)                         │
│  ┌───────────────────┐    ┌───────────────────┐            │
│  │     Ollama        │    │     Whisper       │            │
│  │  Qwen 2.5 14B     │    │  large-v3         │            │
│  │  :11434           │    │  :9001            │            │
│  │  ~10-12GB VRAM    │    │  ~3-4GB VRAM      │            │
│  └─────────▲─────────┘    └─────────▲─────────┘            │
│            │                        │                       │
│            └────────┬───────────────┘                       │
│                     │ host.docker.internal                  │
│  ┌──────────────────┴──────────────────────────────────────┐│
│  │                    Docker Network                        ││
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ││
│  │  │   PHP83      │  │  Centrifugo  │  │    Redis     │  ││
│  │  │  Symfony     │  │  WebSocket   │  │   Cache      │  ││
│  │  └──────────────┘  └──────────────┘  └──────────────┘  ││
│  └──────────────────────────────────────────────────────────┘│
└─────────────────────────────────────────────────────────────┘
```

---

## 🧠 Ollama (LLM)

### Быстрая Установка

```bash
# macOS
brew install ollama
brew services start ollama

# Linux
curl -fsSL https://ollama.com/install.sh | sh
sudo systemctl enable ollama
sudo systemctl start ollama

# Загрузить модель
ollama pull qwen2.5:14b-instruct-q4_K_M
```

### Рекомендуемые Модели

| Модель | VRAM | Скорость | Качество | Рекомендация |
|--------|------|----------|----------|--------------|
| `qwen2.5:14b-instruct-q4_K_M` | ~10-12GB | 3-5s | ⭐⭐⭐⭐⭐ | **Production** |
| `qwen2.5:7b-instruct-q4_K_M` | ~5-6GB | 2-3s | ⭐⭐⭐⭐ | Mid-tier GPU |
| `qwen2.5:3b-instruct-q4_K_M` | ~2-3GB | 1-2s | ⭐⭐⭐ | CPU only |

### Конфигурация для Production

```bash
# Linux: создать override для systemd
sudo mkdir -p /etc/systemd/system/ollama.service.d/

cat << 'EOF' | sudo tee /etc/systemd/system/ollama.service.d/override.conf
[Service]
Environment="OLLAMA_HOST=0.0.0.0"
Environment="OLLAMA_NUM_PARALLEL=4"
Environment="OLLAMA_MAX_LOADED_MODELS=2"
Environment="OLLAMA_KEEP_ALIVE=10m"
EOF

sudo systemctl daemon-reload
sudo systemctl restart ollama
```

### Проверка

```bash
# Статус сервиса
curl http://localhost:11434/api/tags

# Тест генерации
curl -X POST http://localhost:11434/api/generate \
  -H "Content-Type: application/json" \
  -d '{
    "model": "qwen2.5:14b-instruct-q4_K_M",
    "prompt": "Создай JSON для задачи купить молоко",
    "format": "json",
    "stream": false
  }'
```

---

## 🎤 Whisper (STT)

### Быстрая Установка (faster-whisper-server)

```bash
# Создать виртуальное окружение
python3 -m venv ~/.venv/whisper
source ~/.venv/whisper/bin/activate

# Установить faster-whisper-server
pip install faster-whisper-server

# Запустить
faster-whisper-server --host 0.0.0.0 --port 9001 --model large-v3
```

### Рекомендуемые Модели

| Модель | VRAM | Скорость | Точность | Рекомендация |
|--------|------|----------|----------|--------------|
| `large-v3` | ~3-4GB | 0.5-1s | ⭐⭐⭐⭐⭐ | **Production** |
| `medium` | ~2GB | 0.3-0.5s | ⭐⭐⭐⭐ | Хороший баланс |
| `small` | ~1GB | 0.2-0.3s | ⭐⭐⭐ | CPU fallback |

### Systemd Сервис (Production)

```bash
# Создать директорию и окружение
sudo mkdir -p /opt/whisper
sudo python3 -m venv /opt/whisper/venv
sudo /opt/whisper/venv/bin/pip install faster-whisper-server

# Systemd сервис
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

sudo systemctl daemon-reload
sudo systemctl enable faster-whisper
sudo systemctl start faster-whisper
```

### API Endpoints

```bash
# Health check
curl http://localhost:9001/health

# Транскрипция (OpenAI-compatible API)
curl -X POST http://localhost:9001/v1/audio/transcriptions \
  -F "file=@audio.wav" \
  -F "language=ru" \
  -F "response_format=json"
```

---

## 📡 Centrifugo (WebSocket)

### Конфигурация (Docker)

Centrifugo остается в Docker, так как не требует GPU:

```json
// infrastructure/docker/configs/centrifugo/config.json
{
  "token_hmac_secret_key": "your-secret-key-min-32-chars",
  "api_key": "your-api-key-min-32-chars",
  "admin": true,
  "admin_password": "admin-password",
  "admin_secret": "admin-secret",

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
      "history_ttl": "60s"
    },
    {
      "name": "tasks",
      "presence": false,
      "history_size": 100,
      "history_ttl": "300s"
    }
  ],

  "health": true,
  "prometheus": true
}
```

### Проверка

```bash
# Health check
curl http://localhost:8000/health

# Admin UI
# http://localhost:8000/admin (если включен)
```

---

## ✅ Интеграционное Тестирование

### Полный Чеклист

```bash
#!/bin/bash
# scripts/test-ai-services.sh

echo "=== AI Services Integration Test ==="

# 1. Ollama
echo -e "\n[1/4] Testing Ollama..."
OLLAMA_RESULT=$(curl -s http://localhost:11434/api/tags)
if echo "$OLLAMA_RESULT" | grep -q "qwen2.5"; then
    echo "✅ Ollama: OK"
else
    echo "❌ Ollama: FAILED"
fi

# 2. Whisper
echo -e "\n[2/4] Testing Whisper..."
WHISPER_RESULT=$(curl -s http://localhost:9001/health)
if echo "$WHISPER_RESULT" | grep -q "ok\|healthy"; then
    echo "✅ Whisper: OK"
else
    echo "❌ Whisper: FAILED"
fi

# 3. Centrifugo
echo -e "\n[3/4] Testing Centrifugo..."
CENTRIFUGO_RESULT=$(curl -s http://localhost:8000/health)
if [ ! -z "$CENTRIFUGO_RESULT" ]; then
    echo "✅ Centrifugo: OK"
else
    echo "❌ Centrifugo: FAILED"
fi

# 4. Docker → Native access
echo -e "\n[4/4] Testing Docker → Native access..."
DOCKER_OLLAMA=$(docker exec backend-php83 curl -s http://host.docker.internal:11434/api/tags 2>/dev/null)
if echo "$DOCKER_OLLAMA" | grep -q "qwen2.5"; then
    echo "✅ Docker → Ollama: OK"
else
    echo "❌ Docker → Ollama: FAILED (check extra_hosts)"
fi

DOCKER_WHISPER=$(docker exec backend-php83 curl -s http://host.docker.internal:9001/health 2>/dev/null)
if echo "$DOCKER_WHISPER" | grep -q "ok\|healthy"; then
    echo "✅ Docker → Whisper: OK"
else
    echo "❌ Docker → Whisper: FAILED (check extra_hosts)"
fi

echo -e "\n=== Test Complete ==="
```

### Тест LLM Генерации

```bash
# Тест с JSON output
curl -X POST http://localhost:11434/api/generate \
  -H "Content-Type: application/json" \
  -d '{
    "model": "qwen2.5:14b-instruct-q4_K_M",
    "prompt": "Ты AI ассистент для управления задачами. Преобразуй команду в JSON:\n\nКоманда: Создай задачу купить молоко на завтра с приоритетом высокий\n\nОтветь только JSON:",
    "format": "json",
    "stream": false,
    "options": {
      "temperature": 0.3
    }
  }' | jq '.response'
```

---

## 🚀 Production Конфигурация

### Требования к GPU Серверу

```yaml
Рекомендуемая конфигурация:
  GPU: NVIDIA RTX 4090 24GB
  Memory Bandwidth: 1008 GB/s
  CPU: 8+ ядер
  RAM: 32GB+
  Storage: 100GB NVMe SSD

Использование VRAM:
  Qwen 2.5 14B q4_K_M: ~10-12GB
  Whisper large-v3: ~3-4GB
  Total: ~14-16GB (запас 8GB)

Производительность:
  LLM Response: 3-5 секунд
  STT Processing: 0.5-1 секунда
  Full Pipeline: < 6 секунд
```

### Альтернативные GPU

| GPU | VRAM | Цена/мес | Модели |
|-----|------|----------|--------|
| RTX 4090 | 24GB | ~29,000₽ | 14B + large-v3 ✅ |
| RTX 4080 | 16GB | ~20,000₽ | 14B + medium |
| A4000 | 16GB | ~9,000₽ | 7B + medium |
| 2x V100 | 32GB | ~26,000₽ | 14B + large-v3 |

### Environment Variables

```bash
# /etc/environment или .bashrc

# Ollama
OLLAMA_HOST=0.0.0.0
OLLAMA_NUM_PARALLEL=4
OLLAMA_MAX_LOADED_MODELS=2
OLLAMA_KEEP_ALIVE=10m

# Whisper (если нужно)
WHISPER_MODEL=large-v3
WHISPER_DEVICE=cuda
```

---

## 🔧 Устранение Неполадок

### Ollama не запускается

```bash
# macOS
brew services restart ollama
tail -f ~/Library/Logs/Homebrew/ollama.log

# Linux
sudo systemctl restart ollama
sudo journalctl -u ollama -f

# Проверить порт
lsof -i :11434
```

### Whisper не использует GPU

```bash
# Проверить CUDA
python -c "import torch; print(torch.cuda.is_available())"

# Переустановить
pip uninstall faster-whisper faster-whisper-server
pip install faster-whisper-server

# Запустить с явным GPU
faster-whisper-server --device cuda --compute-type float16
```

### Docker не видит нативные сервисы

```bash
# Проверить extra_hosts
docker inspect backend-php83 | grep -A5 ExtraHosts

# Linux: добавить в docker-compose.yml
extra_hosts:
  - "host.docker.internal:host-gateway"
```

### Медленная генерация

```bash
# Проверить GPU
nvidia-smi

# Проверить что модель в памяти
ollama ps

# Увеличить параллелизм
export OLLAMA_NUM_PARALLEL=4
```

### Ошибки памяти

```bash
# Проверить VRAM
nvidia-smi --query-gpu=memory.used,memory.total --format=csv

# Использовать меньшую модель
ollama pull qwen2.5:7b-instruct-q4_K_M

# Whisper: medium вместо large-v3
faster-whisper-server --model medium
```

---

## 📊 Мониторинг

### GPU Мониторинг

```bash
# Реальное время
watch -n 1 nvidia-smi

# Логирование
nvidia-smi --query-gpu=timestamp,name,memory.used,utilization.gpu --format=csv -l 1 > gpu.log
```

### Логи Сервисов

```bash
# Ollama (Linux)
journalctl -u ollama -f

# Whisper (Linux)
journalctl -u faster-whisper -f

# Centrifugo (Docker)
docker logs -f centrifugo
```

---

## ✅ Следующие Шаги

1. ✅ AI сервисы установлены нативно
2. ✅ Centrifugo настроен в Docker
3. → Перейти к [Безопасность и Сеть](04_SECURITY.md)
4. → Затем к [Backend Services](../02_BACKEND/02_SERVICES.md)

---

**Статус Документа**: Обновлен для нативной архитектуры
**Последнее Тестирование**: 2025-11-27
**Автор**: AI Architecture Team

---

## 📝 История Изменений

### v2.0.0 (2025-11-27)
- **КРИТИЧЕСКОЕ ИЗМЕНЕНИЕ**: Полный переход на нативную установку
- Удалены Docker конфигурации для Ollama/Whisper
- Добавлен faster-whisper-server вместо custom Flask
- Обновлены модели: Qwen 2.5 14B, Whisper large-v3
- Добавлена информация о RTX 4090 для production
- Добавлен integration test script

### v1.0.0 (2025-11-08)
- Первоначальная версия (Docker-based)
