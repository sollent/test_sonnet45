#!/bin/sh
# Entrypoint script for Ollama container
# Automatically downloads and runs specified models

set -e

echo "Starting Ollama server..."

# Start Ollama in the background
ollama serve &
OLLAMA_PID=$!

# Wait for Ollama to be ready
echo "Waiting for Ollama to start..."
sleep 5

# Function to check if Ollama is ready
wait_for_ollama() {
    until curl -s http://localhost:11434/api/tags > /dev/null 2>&1; do
        echo "Waiting for Ollama API to be ready..."
        sleep 2
    done
    echo "Ollama API is ready!"
}

wait_for_ollama

# Pull models specified in environment variable
# ⚠️ DISABLED: Auto-pull causes container restart issues
# User should manually pull models after container starts:
# docker exec -it claude-ai-assistant-ollama ollama pull mistral:7b
#
# if [ -n "$OLLAMA_MODELS" ]; then
#     IFS=',' # Split by comma
#     for model in $OLLAMA_MODELS; do
#         model=$(echo "$model" | tr -d ' ') # Remove spaces
#         echo "Pulling model: $model"
#         ollama pull "$model" || echo "Failed to pull $model, continuing..."
#     done
# fi

# List available models
echo "Available models:"
ollama list

echo "Ollama is ready to serve!"

# Keep the container running
wait $OLLAMA_PID