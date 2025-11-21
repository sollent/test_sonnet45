import { ref, onMounted, onUnmounted } from 'vue';
import { websocketService, WebSocketEvent, EventHandler } from '@/services/websocket.service';
import { useAuthStore } from '@/stores/auth.store';

export function useWebSocket() {
  const isConnected = ref(false);
  const lastEvent = ref<WebSocketEvent | null>(null);
  const authStore = useAuthStore();

  const unsubscribers: Array<() => void> = [];

  /**
   * Connect to WebSocket when user is authenticated
   */
  const connect = async () => {
    if (!authStore.isAuthenticated) {
      console.warn('[useWebSocket] Cannot connect: user not authenticated');
      return;
    }

    try {
      await websocketService.connect();
      isConnected.value = websocketService.isConnected();
    } catch (error) {
      console.error('[useWebSocket] Connection error:', error);
    }
  };

  /**
   * Disconnect from WebSocket
   */
  const disconnect = () => {
    websocketService.disconnect();
    isConnected.value = false;
  };

  /**
   * Subscribe to specific event type
   */
  const on = (eventType: string, handler: EventHandler) => {
    const unsubscribe = websocketService.on(eventType, (event) => {
      lastEvent.value = event;
      handler(event);
    });
    unsubscribers.push(unsubscribe);
    return unsubscribe;
  };

  /**
   * Subscribe to task-related events
   */
  const onTaskEvent = (handler: (event: WebSocketEvent) => void) => {
    const events = [
      'task.created',
      'task.updated',
      'task.deleted',
      'task.completed',
      'task.reopened',
      'subtask.created',
      'subtask.converted',
    ];

    events.forEach((eventType) => {
      on(eventType, handler);
    });
  };

  /**
   * Subscribe to voice command events
   */
  const onVoiceEvent = (handler: (event: WebSocketEvent) => void) => {
    on('voice.processing', handler);
    on('voice.completed', handler);
    on('voice.error', handler);
  };

  // Auto-connect on mount if authenticated
  onMounted(() => {
    if (authStore.isAuthenticated) {
      connect();
    }

    // Track connection state
    on('connection', (event) => {
      isConnected.value = event.event === 'connected';
    });
  });

  // Cleanup on unmount
  onUnmounted(() => {
    unsubscribers.forEach((unsub) => unsub());
  });

  return {
    isConnected,
    lastEvent,
    connect,
    disconnect,
    on,
    onTaskEvent,
    onVoiceEvent,
  };
}
