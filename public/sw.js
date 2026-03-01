// Service Worker for PXMBoard PWA
// Minimal implementation for scope registration and as a base for push notifications (Story 028).

self.addEventListener('install', event => {
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(clients.claim());
});
