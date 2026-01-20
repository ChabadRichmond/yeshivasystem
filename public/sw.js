// YLT Attendance - Service Worker
// Minimal service worker for PWA installability (network-first, no caching)

const SW_VERSION = '1.0.0';

// Install event - skip waiting to activate immediately
self.addEventListener('install', (event) => {
  console.log('[SW] Installing service worker v' + SW_VERSION);
  self.skipWaiting();
});

// Activate event - claim all clients immediately
self.addEventListener('activate', (event) => {
  console.log('[SW] Activating service worker v' + SW_VERSION);
  event.waitUntil(clients.claim());
});

// Fetch event - network-first (no caching of attendance data)
self.addEventListener('fetch', (event) => {
  // Always go to network, no caching
  event.respondWith(
    fetch(event.request).catch(() => {
      // If network fails and it's a navigation request, show a simple offline message
      if (event.request.mode === 'navigate') {
        return new Response(
          '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Offline</title><style>body{font-family:system-ui,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#f3f4f6;}.box{text-align:center;padding:2rem;background:white;border-radius:1rem;box-shadow:0 4px 6px rgba(0,0,0,0.1);max-width:90%;width:320px;}h1{color:#4f46e5;margin:0 0 1rem;}p{color:#6b7280;margin:0 0 1.5rem;}button{background:#4f46e5;color:white;border:none;padding:0.75rem 1.5rem;border-radius:0.5rem;font-size:1rem;cursor:pointer;}button:hover{background:#4338ca;}</style></head><body><div class="box"><h1>Offline</h1><p>Please check your internet connection and try again.</p><button onclick="location.reload()">Retry</button></div></body></html>',
          {
            status: 503,
            statusText: 'Service Unavailable',
            headers: { 'Content-Type': 'text/html' }
          }
        );
      }
      // For other requests, just fail
      return new Response('Network error', { status: 503 });
    })
  );
});
