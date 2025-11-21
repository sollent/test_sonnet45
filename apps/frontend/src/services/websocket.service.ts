import { Centrifuge, Subscription, PublicationContext } from 'centrifuge';
import { apiClient } from './api.service';

export interface WebSocketTokenResponse {
  token: string;
  channels: Record<string, string>;
  user_id: number;
  websocket_url: string;
}

export interface WebSocketEvent {
  event: string;
  data: unknown;
  timestamp: string;
}

export type EventHandler = (event: WebSocketEvent) => void;

class WebSocketService {
  private centrifuge: Centrifuge | null = null;
  private subscriptions: Map<string, Subscription> = new Map();
  private eventHandlers: Map<string, Set<EventHandler>> = new Map();
  private isConnecting = false;
  private reconnectAttempts = 0;
  private readonly maxReconnectAttempts = 5;

  /**
   * Connect to Centrifugo WebSocket server
   */
  async connect(): Promise<void> {
    if (this.centrifuge?.state === 'connected' || this.isConnecting) {
      return;
    }

    this.isConnecting = true;

    try {
      // Get token from backend
      const tokenData = await this.getToken();

      // Create Centrifuge client
      this.centrifuge = new Centrifuge(tokenData.websocket_url, {
        token: tokenData.token,
        debug: import.meta.env.DEV,
      });

      // Setup event handlers
      this.setupEventHandlers();

      // Connect
      this.centrifuge.connect();

      // Subscribe to user channels
      await this.subscribeToChannels(tokenData.channels);

      this.reconnectAttempts = 0;
    } catch (error) {
      console.error('[WebSocket] Connection failed:', error);
      this.handleReconnect();
    } finally {
      this.isConnecting = false;
    }
  }

  /**
   * Disconnect from WebSocket server
   */
  disconnect(): void {
    if (!this.centrifuge) return;

    // Unsubscribe from all channels
    this.subscriptions.forEach((sub) => {
      sub.unsubscribe();
    });
    this.subscriptions.clear();

    // Disconnect
    this.centrifuge.disconnect();
    this.centrifuge = null;
  }

  /**
   * Subscribe to a specific event type
   */
  on(eventType: string, handler: EventHandler): () => void {
    if (!this.eventHandlers.has(eventType)) {
      this.eventHandlers.set(eventType, new Set());
    }
    this.eventHandlers.get(eventType)!.add(handler);

    // Return unsubscribe function
    return () => {
      this.eventHandlers.get(eventType)?.delete(handler);
    };
  }

  /**
   * Remove event handler
   */
  off(eventType: string, handler: EventHandler): void {
    this.eventHandlers.get(eventType)?.delete(handler);
  }

  /**
   * Check if connected
   */
  isConnected(): boolean {
    return this.centrifuge?.state === 'connected';
  }

  /**
   * Get token from backend API
   */
  private async getToken(): Promise<WebSocketTokenResponse> {
    const response = await apiClient.get<WebSocketTokenResponse>('/api/websocket/token');
    return response.data;
  }

  /**
   * Setup Centrifuge event handlers
   */
  private setupEventHandlers(): void {
    if (!this.centrifuge) return;

    this.centrifuge.on('connected', (ctx) => {
      console.log('[WebSocket] Connected:', ctx);
      this.emit('connection', { event: 'connected', data: ctx, timestamp: new Date().toISOString() });
    });

    this.centrifuge.on('disconnected', (ctx) => {
      console.log('[WebSocket] Disconnected:', ctx);
      this.emit('connection', { event: 'disconnected', data: ctx, timestamp: new Date().toISOString() });
      this.handleReconnect();
    });

    this.centrifuge.on('error', (ctx) => {
      console.error('[WebSocket] Error:', ctx);
      this.emit('error', { event: 'error', data: ctx, timestamp: new Date().toISOString() });
    });
  }

  /**
   * Subscribe to user channels
   */
  private async subscribeToChannels(channelTokens: Record<string, string>): Promise<void> {
    if (!this.centrifuge) return;

    for (const [channel, token] of Object.entries(channelTokens)) {
      const sub = this.centrifuge.newSubscription(channel, {
        token,
      });

      sub.on('publication', (ctx: PublicationContext) => {
        this.handlePublication(channel, ctx);
      });

      sub.on('subscribed', (ctx) => {
        console.log(`[WebSocket] Subscribed to ${channel}:`, ctx);
      });

      sub.on('error', (ctx) => {
        console.error(`[WebSocket] Subscription error for ${channel}:`, ctx);
      });

      sub.subscribe();
      this.subscriptions.set(channel, sub);
    }
  }

  /**
   * Handle incoming publication
   */
  private handlePublication(channel: string, ctx: PublicationContext): void {
    const data = ctx.data as WebSocketEvent;
    console.log(`[WebSocket] Publication on ${channel}:`, data);

    // Emit to specific event handlers
    if (data.event) {
      this.emit(data.event, data);
    }

    // Emit to channel-specific handlers
    this.emit(`channel:${channel}`, data);

    // Emit to wildcard handlers
    this.emit('*', data);
  }

  /**
   * Emit event to handlers
   */
  private emit(eventType: string, event: WebSocketEvent): void {
    const handlers = this.eventHandlers.get(eventType);
    if (handlers) {
      handlers.forEach((handler) => {
        try {
          handler(event);
        } catch (error) {
          console.error(`[WebSocket] Handler error for ${eventType}:`, error);
        }
      });
    }
  }

  /**
   * Handle reconnection logic
   */
  private handleReconnect(): void {
    if (this.reconnectAttempts >= this.maxReconnectAttempts) {
      console.error('[WebSocket] Max reconnect attempts reached');
      return;
    }

    this.reconnectAttempts++;
    const delay = Math.min(1000 * Math.pow(2, this.reconnectAttempts), 30000);

    console.log(`[WebSocket] Reconnecting in ${delay}ms (attempt ${this.reconnectAttempts})`);

    setTimeout(() => {
      this.connect();
    }, delay);
  }
}

export const websocketService = new WebSocketService();
