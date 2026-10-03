const CACHE_NAME = 'levitico-fretes-v2';
const BASE_PATH = '/levitico';
const STATIC_ASSETS = [
  BASE_PATH + '/',
  BASE_PATH + '/manifest.json',
  BASE_PATH + '/offline.html',
  BASE_PATH + '/icons/icon.svg',
  BASE_PATH + '/icons/icon-512.svg'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(STATIC_ASSETS);
    })
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
      );
    })
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  // Apenas métodos GET
  if (event.request.method !== 'GET') {
    return;
  }

  const url = new URL(event.request.url);

  // Não interceptar rotas de download de XML ou XML bruto
  if (url.pathname.endsWith('/xml') || url.searchParams.has('exportar')) {
    return;
  }

  event.respondWith(
    fetch(event.request)
      .then((networkResponse) => {
        // Se a resposta for válida, armazena no cache caso seja asset estático
        if (networkResponse && networkResponse.status === 200 && networkResponse.type === 'basic') {
          if (url.pathname.startsWith(BASE_PATH + '/icons/') || url.pathname.endsWith('.css') || url.pathname.endsWith('.js')) {
            const responseToCache = networkResponse.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(event.request, responseToCache));
          }
        }
        return networkResponse;
      })
      .catch(async () => {
        // Fallback para cache quando offline
        const cachedResponse = await caches.match(event.request);
        if (cachedResponse) {
          return cachedResponse;
        }
        // Se for navegação de página HTML, exibe página offline amigável
        if (event.request.mode === 'navigate' || event.request.headers.get('accept')?.includes('text/html')) {
          return caches.match(BASE_PATH + '/offline.html');
        }
      })
  );
});
