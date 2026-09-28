/*
 * Service worker de la PWA (ByH ERP).
 *
 * El ERP trabaja con datos en vivo, sesión y tokens CSRF: las páginas NUNCA
 * se guardan en caché (siempre van a la red y, sin conexión, se muestra
 * /offline.html). Solo se guardan los archivos estáticos: /build/* (tienen
 * hash en el nombre, así que cada despliegue trae archivos nuevos) e íconos.
 * Los formularios (POST) y las descargas no pasan por el service worker.
 *
 * Al cambiar este archivo, subir VERSION para limpiar las cachés anteriores.
 */
const VERSION = 'v1';
const CACHE_ESTATICOS = `byh-estaticos-${VERSION}`;
const OFFLINE_URL = '/offline.html';

const PRECARGA = [
    OFFLINE_URL,
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/images/logo-dashboard-32.png',
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_ESTATICOS)
            .then(cache => cache.addAll(PRECARGA))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys()
            .then(keys => Promise.all(
                keys.filter(key => key.startsWith('byh-') && key !== CACHE_ESTATICOS).map(key => caches.delete(key))
            ))
            .then(() => self.clients.claim())
    );
});

function esEstatico(url) {
    return url.pathname.startsWith('/build/')
        || url.pathname.startsWith('/icons/')
        || url.pathname.startsWith('/images/');
}

self.addEventListener('fetch', event => {
    const request = event.request;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    // Páginas: siempre desde la red; sin conexión, la página de aviso.
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match(OFFLINE_URL))
        );

        return;
    }

    // Estáticos: primero la caché, si no está se descarga y se guarda.
    if (esEstatico(url)) {
        event.respondWith(
            caches.match(request).then(enCache => enCache || fetch(request).then(respuesta => {
                if (respuesta.ok) {
                    const copia = respuesta.clone();
                    caches.open(CACHE_ESTATICOS).then(cache => cache.put(request, copia));
                }

                return respuesta;
            }))
        );
    }
});
