# 🖥️ Нативная Установка AI Сервисов

> **Версия**: 1.0
> **Дата**: 2025-11-20
> **Статус**: Production-ready

## 📋 Содержание

1. [Обзор](#обзор)
2. [Установка Ollama](#установка-ollama)
3. [Установка Whisper](#установка-whisper)
4. [Проверка Работоспособности](#проверка-работоспособности)
5. [Автозапуск](#автозапуск)
6. [Troubleshooting](#troubleshooting)

---

## 🎯 Обзор

### Почему Нативная Установка?

AI сервисы (Ollama и Whisper) теперь работают **нативно** на хосте вместо Docker контейнеров для значительного улучшения производительности:

| Метрика | Docker | Native | Улучшение |
|---------|--------|--------|-----------|
| **Whisper (tiny)** | 10-15s | 0.5-1s | 10-20x |
| **LLM (Qwen 1.5B)** | 60-90s | 3-5s | 15-20x |
| **Full Pipeline** | 70-105s | 3.5-6s | 15-20x |

### Архитектура

```
┌─────────────────────────────────────────────┐
│                   Host                       │
├─────────────────────────────────────────────┤
│  Ollama (localhost:11434)                   │
│  Whisper API (localhost:9000)               │
└──────────────┬──────────────────────────────┘
               │ host.docker.internal
┌──────────────┴──────────────────────────────┐
│              Docker                          │
├─────────────────────────────────────────────┤
│  PHP Backend (backend-php83)                │
│  Centrifugo (backend-centrifugo)            │
│  Redis (backend-redis)                      │
│  PostgreSQL, Nginx, etc.                    │
└─────────────────────────────────────────────┘
```

---

## 🧠 Установка Ollama

### macOS

```bash
# Через Homebrew (рекомендуется)
brew install ollama

# Или через curl
curl -fsSL https://ollama.com/install.sh | sh
```

### Запуск Сервиса

```bash
# Запустить Ollama сервер
ollama serve

# В отдельном терминале загрузить модель
ollama pull qwen2.5:1.5b
```

### Проверка

```bash
# Проверить что сервис работает
curl http://localhost:11434/api/tags

# Должен вернуть JSON с моделями
```

### Конфигурация (опционально)

```bash
# Установить переменные окружения перед запуском
export OLLAMA_NUM_PARALLEL=2      # Параллельные запросы
export OLLAMA_KEEP_ALIVE=24h       # Держать модель в памяти
export OLLAMA_HOST=0.0.0.0         # Слушать на всех интерфейсах

ollama serve
```

---

## 🎤 Установка Whisper

### Требования

- Python 3.9+
- pip
- ffmpeg (для обработки аудио)

### Установка ffmpeg

```bash
# macOS
brew install ffmpeg

# Ubuntu/Debian
sudo apt install ffmpeg
```

### Создание Виртуального Окружения

```bash
# Создать виртуальное окружение
python3 -m venv ~/whisper-env

# Активировать
source ~/whisper-env/bin/activate

# Установить зависимости
pip install openai-whisper flask
```

### Создание API Сервера

Создайте файл `~/whisper-server.py`:

```python
from flask import Flask, request, jsonify
import whisper
import tempfile
import os

app = Flask(__name__)

# Загружаем модель при старте (tiny для скорости)
print("Loading Whisper model...")
model = whisper.load_model("tiny")
print("Model loaded!")

@app.route('/asr', methods=['POST'])
def transcribe():
    """Транскрипция аудио в текст"""
    try:
        if 'audio_file' not in request.files:
            return jsonify({'error': 'No audio file provided'}), 400

        audio_file = request.files['audio_file']

        # Сохраняем во временный файл
        with tempfile.NamedTemporaryFile(suffix='.wav', delete=False) as tmp:
            audio_file.save(tmp.name)

            # Транскрибируем
            result = model.transcribe(tmp.name, language='ru')

            # Удаляем временный файл
            os.unlink(tmp.name)

            return jsonify({
                'text': result['text'].strip(),
                'language': result.get('language', 'ru'),
                'confidence': 0.9  # Whisper не возвращает confidence
            })

    except Exception as e:
        return jsonify({'error': str(e)}), 500

@app.route('/docs', methods=['GET'])
def health():
    """Health check endpoint"""
    return jsonify({'status': 'ok', 'model': 'tiny'})

if __name__ == '__main__':
    print("Starting Whisper API server on port 9000...")
    app.run(host='0.0.0.0', port=9000, debug=False)
```

### Запуск Сервера

```bash
# Активировать виртуальное окружение
source ~/whisper-env/bin/activate

# Запустить сервер
python ~/whisper-server.py
```

### Проверка

```bash
# Health check
curl http://localhost:9000/docs

# Должен вернуть: {"status": "ok", "model": "tiny"}
```

---

## ✅ Проверка Работоспособности

### 1. Проверка Нативных Сервисов

```bash
# Ollama
curl http://localhost:11434/api/tags

# Whisper
curl http://localhost:9000/docs
```

### 2. Проверка из Docker Контейнера

```bash
# Ollama из PHP контейнера
docker exec backend-php83 curl http://host.docker.internal:11434/api/tags

# Whisper из PHP контейнера
docker exec backend-php83 curl http://host.docker.internal:9000/docs
```

### 3. Функциональный Тест

```bash
# Получить JWT токен (замените на свой)
JWT_TOKEN="your-jwt-token"

# Тест обработки текстовой команды
curl -X POST http://localhost:8090/api/voice/process-text \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"text": "Создай задачу купить молоко на завтра"}'
```

---

## 🔄 Автозапуск

### macOS (LaunchAgent)

#### Ollama

Файл: `~/Library/LaunchAgents/com.ollama.plist`

```xml
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
    <key>Label</key>
    <string>com.ollama</string>
    <key>ProgramArguments</key>
    <array>
        <string>/opt/homebrew/bin/ollama</string>
        <string>serve</string>
    </array>
    <key>RunAtLoad</key>
    <true/>
    <key>KeepAlive</key>
    <true/>
    <key>EnvironmentVariables</key>
    <dict>
        <key>OLLAMA_NUM_PARALLEL</key>
        <string>2</string>
        <key>OLLAMA_KEEP_ALIVE</key>
        <string>24h</string>
    </dict>
</dict>
</plist>
```

#### Whisper

Файл: `~/Library/LaunchAgents/com.whisper.plist`

```xml
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
    <key>Label</key>
    <string>com.whisper</string>
    <key>ProgramArguments</key>
    <array>
        <string>/Users/YOUR_USERNAME/whisper-env/bin/python</string>
        <string>/Users/YOUR_USERNAME/whisper-server.py</string>
    </array>
    <key>RunAtLoad</key>
    <true/>
    <key>KeepAlive</key>
    <true/>
    <key>WorkingDirectory</key>
    <string>/Users/YOUR_USERNAME</string>
</dict>
</plist>
```

#### Активация

```bash
# Загрузить сервисы
launchctl load ~/Library/LaunchAgents/com.ollama.plist
launchctl load ~/Library/LaunchAgents/com.whisper.plist

# Проверить статус
launchctl list | grep ollama
launchctl list | grep whisper

# Остановить/запустить
launchctl stop com.ollama
launchctl start com.ollama
```

### Linux (SystemD)

См. секцию 1.3 в MIGRATION_PLAN.md для полных инструкций SystemD.

---

## 🔧 Troubleshooting

### Ollama не запускается

```bash
# Проверить логи
cat ~/.ollama/logs/server.log

# Проверить порт
lsof -i :11434

# Убить процесс если занят
kill $(lsof -t -i :11434)
```

### Whisper не запускается

```bash
# Проверить Python
which python
python --version

# Проверить зависимости
pip list | grep whisper
pip list | grep flask

# Проверить порт
lsof -i :9000
```

### Docker не видит нативные сервисы

```bash
# Проверить host.docker.internal
docker exec backend-php83 ping -c 1 host.docker.internal

# Если не работает, добавить в /etc/hosts внутри контейнера
docker exec backend-php83 sh -c 'echo "host-gateway host.docker.internal" >> /etc/hosts'
```

### Ошибка "Model not found"

```bash
# Проверить загруженные модели
ollama list

# Загрузить модель заново
ollama pull qwen2.5:1.5b
```

### Медленная работа Whisper

```bash
# Проверить что используется Metal на macOS
pip install --upgrade openai-whisper

# Или использовать larger модель для точности
# Изменить в whisper-server.py:
# model = whisper.load_model("small")  # вместо "tiny"
```

---

## 📊 Мониторинг

### Ollama

```bash
# Статус моделей
ollama list

# Запущенные модели
ollama ps

# Остановить все модели
ollama stop $(ollama ps | tail -n +2 | awk '{print $1}')
```

### Whisper

```bash
# Проверить процесс
ps aux | grep whisper

# Логи (если запущен через launchd)
tail -f ~/Library/Logs/com.whisper.log
```

---

## 🔗 Связанные Документы

- [MIGRATION_PLAN.md](../../MIGRATION_PLAN.md) - План миграции
- [03_AI_SERVICES.md](01_INFRASTRUCTURE/03_AI_SERVICES.md) - Общая документация AI сервисов
- [VOICE_AI_TESTING_REPORT.md](../guides/voice-ai/VOICE_AI_TESTING_REPORT.md) - Тестирование Voice AI

---

**Автор**: Claude Code AI
**Дата создания**: 2025-11-20
