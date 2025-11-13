// Custom Service Worker extensions for offline detection

self.addEventListener('fetch', (event) => {
  const { request } = event

  // Only handle API requests
  if (!request.url.includes('/api/')) {
    return
  }

  event.respondWith(
    fetch(request)
      .then((response) => {
        // Clone the response before caching
        const responseClone = response.clone()

        // Cache successful responses
        if (response.status === 200) {
          caches.open('api-cache').then((cache) => {
            cache.put(request, responseClone)
          })
        }

        return response
      })
      .catch(async (error) => {
        // Network failed, try to get from cache
        const cachedResponse = await caches.match(request)

        if (cachedResponse) {
          // We have cached data, return it
          return cachedResponse
        }

        // No cache available - notify the app
        self.clients.matchAll().then((clients) => {
          clients.forEach((client) => {
            client.postMessage({
              type: 'NETWORK_ERROR',
              url: request.url,
              method: request.method,
              timestamp: Date.now()
            })
          })
        })

        // Return a custom offline response for API requests
        return new Response(
          JSON.stringify({
            error: 'offline',
            message: 'Content unavailable offline',
            cached: false,
            url: request.url
          }),
          {
            status: 503,
            statusText: 'Service Unavailable',
            headers: {
              'Content-Type': 'application/json',
              'X-Offline-Response': 'true'
            }
          }
        )
      })
  )
})

// Listen for skip waiting message
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting()
  }
})

// Clean old caches on activate
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames
          .filter((cacheName) => {
            // Keep current caches, remove old ones
            return !cacheName.includes('workbox-precache') &&
                   !cacheName.includes('api-cache') &&
                   !cacheName.includes('images-cache') &&
                   !cacheName.includes('fonts-cache')
          })
          .map((cacheName) => caches.delete(cacheName))
      )
    })
  )
})