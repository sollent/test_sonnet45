# 🎤 Phase 3.1: Voice Recording - Frontend Implementation

> **For AI**: Simple voice recording in Vue.js. Copy, adapt, done.

## 🎯 What We're Building

1. Voice recording button
2. Record audio in browser
3. Send to backend
4. Show real-time updates via WebSocket

---

## 📦 Step 1: Voice Recording Composable

**AI**: Create `frontend/src/composables/useVoiceRecording.ts`:

```typescript
import { ref, computed } from 'vue';
import voiceCommandService from '@/services/voiceCommand.service';

export function useVoiceRecording() {
    const isRecording = ref(false);
    const isProcessing = ref(false);
    const mediaRecorder = ref<MediaRecorder | null>(null);
    const audioChunks = ref<Blob[]>([]);
    const error = ref<string | null>(null);

    // Start recording
    const startRecording = async () => {
        try {
            error.value = null;

            // Request microphone access
            const stream = await navigator.mediaDevices.getUserMedia({
                audio: {
                    echoCancellation: true,
                    noiseSuppression: true,
                    sampleRate: 16000
                }
            });

            // Create MediaRecorder
            const recorder = new MediaRecorder(stream, {
                mimeType: 'audio/webm;codecs=opus'
            });

            audioChunks.value = [];

            recorder.ondataavailable = (event) => {
                if (event.data.size > 0) {
                    audioChunks.value.push(event.data);
                }
            };

            recorder.onstop = async () => {
                // Create audio blob
                const audioBlob = new Blob(audioChunks.value, {
                    type: 'audio/webm'
                });

                // Send to backend
                await sendToBackend(audioBlob);

                // Stop stream
                stream.getTracks().forEach(track => track.stop());
            };

            recorder.start();
            mediaRecorder.value = recorder;
            isRecording.value = true;

        } catch (err: any) {
            error.value = err.message || 'Failed to start recording';
            console.error('Recording error:', err);
        }
    };

    // Stop recording
    const stopRecording = () => {
        if (mediaRecorder.value && isRecording.value) {
            mediaRecorder.value.stop();
            isRecording.value = false;
        }
    };

    // Send audio to backend
    const sendToBackend = async (audioBlob: Blob) => {
        try {
            isProcessing.value = true;

            const commandId = await voiceCommandService.submitAudioCommand(audioBlob);

            console.log('Command submitted:', commandId);

            // WebSocket will notify when done
            return commandId;

        } catch (err: any) {
            error.value = err.message || 'Failed to send audio';
            console.error('Send error:', err);
        } finally {
            isProcessing.value = false;
        }
    };

    // Send text command
    const sendTextCommand = async (text: string) => {
        try {
            isProcessing.value = true;
            error.value = null;

            const commandId = await voiceCommandService.submitTextCommand(text);

            console.log('Text command submitted:', commandId);
            return commandId;

        } catch (err: any) {
            error.value = err.message || 'Failed to send command';
            console.error('Send error:', err);
        } finally {
            isProcessing.value = false;
        }
    };

    return {
        isRecording,
        isProcessing,
        error,
        startRecording,
        stopRecording,
        sendTextCommand
    };
}
```

---

## 🌐 Step 2: Voice Command Service

**AI**: Create `frontend/src/services/voiceCommand.service.ts`:

```typescript
import axios from 'axios';

class VoiceCommandService {
    private baseUrl = '/api/voice';

    /**
     * Submit audio command
     */
    async submitAudioCommand(audioBlob: Blob): Promise<string> {
        const formData = new FormData();
        formData.append('audio', audioBlob, 'recording.webm');
        formData.append('source', 'web');

        const response = await axios.post(`${this->baseUrl}/command`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        return response.data.command_id;
    }

    /**
     * Submit text command
     */
    async submitTextCommand(text: string): Promise<string> {
        const response = await axios.post(`${this.baseUrl}/command`, {
            text,
            source: 'web'
        });

        return response.data.command_id;
    }

    /**
     * Get command status
     */
    async getCommandStatus(commandId: string) {
        const response = await axios.get(`${this.baseUrl}/command/${commandId}`);
        return response.data;
    }

    /**
     * Get command history
     */
    async getHistory(days: number = 7) {
        const response = await axios.get(`${this.baseUrl}/history`, {
            params: { days }
        });
        return response.data.commands;
    }
}

export default new VoiceCommandService();
```

---

## 🎨 Step 3: Voice Assistant Component

**AI**: Create `frontend/src/components/VoiceAssistant/VoiceButton.vue`:

```vue
<template>
  <div class="voice-assistant">
    <!-- Voice Button -->
    <button
      @click="toggleRecording"
      :class="['voice-btn', { recording: isRecording, processing: isProcessing }]"
      :disabled="isProcessing"
    >
      <i v-if="!isRecording && !isProcessing" class="pi pi-microphone"></i>
      <i v-if="isRecording" class="pi pi-stop-circle"></i>
      <i v-if="isProcessing" class="pi pi-spin pi-spinner"></i>
    </button>

    <!-- Status Text -->
    <div v-if="statusText" class="status-text">
      {{ statusText }}
    </div>

    <!-- Error Message -->
    <div v-if="error" class="error-message">
      {{ error }}
    </div>

    <!-- Recent Result -->
    <div v-if="lastResult" class="result-message">
      {{ lastResult.message }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useVoiceRecording } from '@/composables/useVoiceRecording';
import { useWebSocket } from '@/composables/useWebSocket';

const {
  isRecording,
  isProcessing,
  error,
  startRecording,
  stopRecording
} = useVoiceRecording();

const { onVoiceEvent } = useWebSocket();
const lastResult = ref<any>(null);

// Status text
const statusText = computed(() => {
  if (isRecording.value) return 'Запись...';
  if (isProcessing.value) return 'Обработка...';
  return '';
});

// Toggle recording
const toggleRecording = () => {
  if (isRecording.value) {
    stopRecording();
  } else {
    startRecording();
  }
};

// Listen for WebSocket updates
onVoiceEvent('completed', (data) => {
  lastResult.value = data.result;

  // Clear after 3 seconds
  setTimeout(() => {
    lastResult.value = null;
  }, 3000);
});
</script>

<style scoped>
.voice-assistant {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}

.voice-btn {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  border: none;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  font-size: 24px;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.voice-btn:hover {
  transform: scale(1.05);
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
}

.voice-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.voice-btn.recording {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  animation: pulse 1.5s infinite;
}

.voice-btn.processing {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

@keyframes pulse {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.1);
  }
}

.status-text {
  font-size: 14px;
  color: #666;
  font-weight: 500;
}

.error-message {
  color: #e74c3c;
  font-size: 14px;
  padding: 8px 16px;
  background: #fee;
  border-radius: 8px;
}

.result-message {
  color: #27ae60;
  font-size: 14px;
  padding: 8px 16px;
  background: #efe;
  border-radius: 8px;
  animation: slideIn 0.3s ease;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
```

---

## 📡 Step 4: WebSocket Integration

**AI**: Create `frontend/src/composables/useWebSocket.ts`:

```typescript
import { ref, onMounted, onUnmounted } from 'vue';
import { Centrifuge } from 'centrifuge';
import { useAuthStore } from '@/stores/auth';

export function useWebSocket() {
    const authStore = useAuthStore();
    const client = ref<Centrifuge | null>(null);
    const connected = ref(false);

    const connect = () => {
        const wsToken = authStore.centrifugoToken; // Get from auth

        client.value = new Centrifuge('ws://localhost:8000/connection/websocket', {
            token: wsToken,
            debug: process.env.NODE_ENV === 'development'
        });

        client.value.on('connected', () => {
            connected.value = true;
            console.log('WebSocket connected');
        });

        client.value.on('disconnected', () => {
            connected.value = false;
            console.log('WebSocket disconnected');
        });

        client.value.connect();

        // Subscribe to user's voice channel
        const userId = authStore.user?.id;
        if (userId) {
            const channel = `voice:user#${userId}`;
            const subscription = client.value.newSubscription(channel);

            subscription.on('publication', (ctx) => {
                handleVoiceEvent(ctx.data);
            });

            subscription.subscribe();
        }
    };

    const disconnect = () => {
        if (client.value) {
            client.value.disconnect();
        }
    };

    const handleVoiceEvent = (data: any) => {
        console.log('Voice event:', data);

        // Emit events based on type
        const eventType = data.event;

        if (eventType === 'command.completed') {
            voiceEventHandlers.value.completed?.forEach(handler => handler(data.data));
        } else if (eventType === 'command.failed') {
            voiceEventHandlers.value.failed?.forEach(handler => handler(data.data));
        } else if (eventType === 'command.transcribed') {
            voiceEventHandlers.value.transcribed?.forEach(handler => handler(data.data));
        }
    };

    const voiceEventHandlers = ref<Record<string, Function[]>>({
        completed: [],
        failed: [],
        transcribed: []
    });

    const onVoiceEvent = (event: string, handler: Function) => {
        if (!voiceEventHandlers.value[event]) {
            voiceEventHandlers.value[event] = [];
        }
        voiceEventHandlers.value[event].push(handler);
    };

    onMounted(() => {
        connect();
    });

    onUnmounted(() => {
        disconnect();
    });

    return {
        connected,
        onVoiceEvent
    };
}
```

---

## 🎯 Step 5: Add to Main View

**AI**: Add to your main task view:

```vue
<template>
  <div class="tasks-view">
    <!-- Existing task list -->
    <TaskList />

    <!-- Voice Assistant Button (floating) -->
    <div class="voice-assistant-fab">
      <VoiceButton />
    </div>
  </div>
</template>

<script setup>
import VoiceButton from '@/components/VoiceAssistant/VoiceButton.vue';
</script>

<style scoped>
.voice-assistant-fab {
  position: fixed;
  bottom: 24px;
  right: 24px;
  z-index: 1000;
}
</style>
```

---

## ✅ Testing

**AI**: Test in browser:

1. Open app in Chrome/Firefox
2. Click microphone button
3. Allow microphone access
4. Say: "Создай задачу купить молоко"
5. Click stop
6. Check WebSocket events in console
7. Check task created

---

## 🚨 Browser Compatibility

Works in:
- ✅ Chrome/Edge (best)
- ✅ Firefox
- ✅ Safari (macOS/iOS)
- ⚠️ Mobile browsers (test on device)

---

## 📊 Flow Diagram

```
User clicks button
      ↓
Request mic permission
      ↓
Start recording
      ↓
User speaks
      ↓
Click stop
      ↓
Create Blob
      ↓
Send to /api/voice/command
      ↓
Show "Processing..."
      ↓
WebSocket receives "completed"
      ↓
Show result message
      ↓
Auto-hide after 3s
```

---

## ✅ Checklist

- [ ] Created useVoiceRecording composable
- [ ] Created voiceCommand service
- [ ] Created VoiceButton component
- [ ] Created useWebSocket composable
- [ ] Added to main view
- [ ] Tested recording
- [ ] Tested WebSocket updates

---

**Time to Implement**: 1-2 hours
**Complexity**: Medium
**Browser Requirement**: HTTPS or localhost (for mic access)