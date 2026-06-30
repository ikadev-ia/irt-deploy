// ════════════════════════════════════════════
// E-Pharma — Service Worker PWA
// ════════════════════════════════════════════

const CACHE_NAME = 'epharma-v1';
const OFFLINE_URL = '/offline/';

// Ressources à mettre en cache immédiatement
const STATIC_ASSETS = [
  '/',
  '/medicaments/',
  '/pharmacies/',
  '/panier/',
  '/static/manifest.json',
  '/static/img/logo.png',
  '/static/img/icon-192x192.png',
  '/static/img/icon-512x512.png',
  'https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css',
  'https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Poppins:wght@400;500;600;700&display=swap',
];

// ─── INSTALLATION ───────────────────────────
self.addEventListener('install', event => {
  console.log('[SW] Installation en cours...');
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      console.log('[SW] Mise en cache des ressources statiques');
      return cache.addAll(STATIC_ASSETS.map(url => new Request(url, { credentials: 'same-origin' }))).catch(err => {
        console.warn('[SW] Certaines ressources non mises en cache :', err);
      });
    })
  );
  self.skipWaiting();
});

// ─── ACTIVATION ─────────────────────────────
self.addEventListener('activate', event => {
  console.log('[SW] Activation...');
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames
          .filter(name => name !== CACHE_NAME)
          .map(name => {
            console.log('[SW] Suppression ancien cache :', name);
            return caches.delete(name);
          })
      );
    })
  );
  self.clients.claim();
});

// ─── STRATÉGIE DE CACHE ─────────────────────
self.addEventListener('fetch', event => {
  const { request } = event;
  const url = new URL(request.url);

  // Ignorer les requêtes non-GET et les APIs externes
  if (request.method !== 'GET') return;
  if (url.origin !== location.origin && !url.hostname.includes('cdnjs') && !url.hostname.includes('fonts')) return;

  // Pages Django — Network First (fraîches si possible, cache sinon)
  if (url.origin === location.origin && !url.pathname.startsWith('/static/')) {
    event.respondWith(
      fetch(request)
        .then(response => {
          if (response && response.status === 200) {
            const responseClone = response.clone();
            caches.open(CACHE_NAME).then(cache => cache.put(request, responseClone));
          }
          return response;
        })
        .catch(() => {
          return caches.match(request).then(cached => {
            if (cached) return cached;
            // Page hors ligne si rien en cache
            return new Response(`
              <!DOCTYPE html>
              <html lang="fr">
              <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <title>E-Pharma — Hors ligne</title>
                <style>
                  body { font-family: Arial, sans-serif; display:flex; flex-direction:column; align-items:center; justify-content:center; min-height:100vh; margin:0; background:#f8f9fa; color:#333; text-align:center; padding:20px; }
                  .icon { font-size: 4rem; margin-bottom: 1rem; }
                  h1 { color: #1aab5f; font-size: 1.8rem; }
                  p { color: #666; max-width: 400px; }
                  a { background:#1aab5f; color:white; padding:12px 24px; border-radius:30px; text-decoration:none; margin-top:20px; display:inline-block; font-weight:bold; }
                </style>
              </head>
              <body>
                <div class="icon">📶</div>
                <h1>Vous êtes hors ligne</h1>
                <p>E-Pharma nécessite une connexion internet pour afficher les médicaments et pharmacies.</p>
                <a href="/" onclick="location.reload()">🔄 Réessayer</a>
              </body>
              </html>
            `, { headers: { 'Content-Type': 'text/html; charset=utf-8' } });
          });
        })
    );
    return;
  }

  // Ressources statiques — Cache First
  event.respondWith(
    caches.match(request).then(cached => {
      if (cached) return cached;
      return fetch(request).then(response => {
        if (response && response.status === 200) {
          const responseClone = response.clone();
          caches.open(CACHE_NAME).then(cache => cache.put(request, responseClone));
        }
        return response;
      });
    })
  );
});

// ─── NOTIFICATIONS PUSH (optionnel) ─────────
self.addEventListener('push', event => {
  if (event.data) {
    const data = event.data.json();
    self.registration.showNotification(data.title || 'E-Pharma', {
      body: data.body || 'Vous avez une nouvelle notification',
      icon: '/static/img/icon-192x192.png',
      badge: '/static/img/icon-72x72.png',
      vibrate: [200, 100, 200],
    });
  }
});
