/**
 * Push Notification Service Worker
 * 
 * This service worker handles incoming push notifications and 
 * displays them to the user using the Notification API.
 */

'use strict';

// Cache version and name
const CACHE_VERSION = 'v1';
const CACHE_NAME = 'push-notify-cache-' + CACHE_VERSION;

// Files to cache initially
const INITIAL_CACHED_RESOURCES = [
    '/offline.html',
    '/default-icon.png'
];

// Install event - cache initial resources
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('[Service Worker] Pre-caching offline resources');
                return cache.addAll(INITIAL_CACHED_RESOURCES);
            })
            .then(() => {
                console.log('[Service Worker] Installation completed');
                return self.skipWaiting();
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys()
            .then(cacheNames => {
                return cacheNames.filter(cacheName => {
                    return cacheName.startsWith('push-notify-cache-') && cacheName !== CACHE_NAME;
                });
            })
            .then(cachesToDelete => {
                return Promise.all(cachesToDelete.map(cacheToDelete => {
                    console.log('[Service Worker] Deleting old cache:', cacheToDelete);
                    return caches.delete(cacheToDelete);
                }));
            })
            .then(() => {
                console.log('[Service Worker] Activation completed');
                return self.clients.claim();
            })
    );
});

// Fetch event - serve from cache if available
self.addEventListener('fetch', event => {
    // Only handle GET requests
    if (event.request.method !== 'GET') return;

    event.respondWith(
        fetch(event.request)
            .catch(() => {
                // When network fails, serve from cache
                return caches.match(event.request)
                    .then(response => {
                        if (response) {
                            console.log('[Service Worker] Serving from cache:', event.request.url);
                            return response;
                        }

                        // If nothing found in cache, serve offline page for document requests
                        if (event.request.mode === 'navigate') {
                            console.log('[Service Worker] Serving offline page');
                            return caches.match('/offline.html');
                        }

                        // If it's an image request, serve default icon
                        if (event.request.destination === 'image') {
                            console.log('[Service Worker] Serving default image');
                            return caches.match('/default-icon.png');
                        }

                        // For other resources, just fail
                        return new Response('Network error happened', {
                            status: 408,
                            headers: { 'Content-Type': 'text/plain' }
                        });
                    });
            })
    );
});

/**
 * Push event handler
 * 
 * Triggered when the browser receives a push notification.
 * Parses the push data and shows a notification.
 */
self.addEventListener('push', event => {
    console.log('[Service Worker] Push notification received');

    if (!event.data) {
        console.warn('[Service Worker] Push event but no data received');
        return;
    }

    let data;
    try {
        data = event.data.json();
    } catch (e) {
        console.error('[Service Worker] Error parsing push data:', e);
        data = {
            title: 'New Notification',
            body: event.data.text(),
            icon: '/default-icon.png'
        };
    }

    // Ensure we have at least default values
    const title = data.title || 'New Notification';
    const options = {
        body: data.body || 'You have a new message',
        icon: data.icon || '/default-icon.png',
        badge: data.badge || '/badge-icon.png',
        data: {
            url: data.url || '/',
            actionId: data.actionId || null
        },
        tag: data.tag || 'push-notification',
        renotify: data.renotify || false,
        requireInteraction: data.requireInteraction !== undefined ? data.requireInteraction : true,
        actions: data.actions || [],
        vibrate: data.vibrate || [100, 50, 100], // Vibration pattern: vibrate, pause, vibrate (in milliseconds)
        silent: data.silent || false
    };

    // Show notification
    event.waitUntil(
        self.registration.showNotification(title, options)
            .then(() => {
                console.log('[Service Worker] Notification displayed successfully');
                // If we need to do anything after displaying notification
                return Promise.resolve();
            })
            .catch(error => {
                console.error('[Service Worker] Error showing notification:', error);
            })
    );
});

/**
 * Notification click event handler
 * 
 * Triggered when the user clicks on a notification.
 * Focuses on an existing tab if open, or opens a new one.
 */
self.addEventListener('notificationclick', event => {
    console.log('[Service Worker] Notification clicked');

    // Close the notification
    event.notification.close();

    // Get URL to open
    const notificationData = event.notification.data;
    let targetUrl = '/';
    
    if (notificationData && notificationData.url) {
        targetUrl = notificationData.url;
    }

    // Handle notification click
    event.waitUntil(
        // Small delay to ensure notification is closed before opening URL
        new Promise(resolve => setTimeout(resolve, 100))
            .then(() => {
                // Check if there's already a tab open with the target URL
                return clients.matchAll({
                    type: 'window',
                    includeUncontrolled: true
                });
            })
            .then(clientList => {
                // Look for matching tab
                for (const client of clientList) {
                    // If we find an exact match, focus it
                    if (client.url === targetUrl && 'focus' in client) {
                        return client.focus();
                    }
                    
                    // Otherwise check if it's the same origin
                    try {
                        const clientUrlObj = new URL(client.url);
                        const targetUrlObj = new URL(targetUrl, self.location.origin);
                        
                        if (clientUrlObj.origin === targetUrlObj.origin && 'focus' in client) {
                            // Navigate the existing client
                            return client.focus().then(client => {
                                if (client && 'navigate' in client) {
                                    return client.navigate(targetUrl);
                                }
                            });
                        }
                    } catch (e) {
                        console.error('[Service Worker] Error comparing URLs:', e);
                    }
                }
                
                // If no matching client found, open a new window/tab
                if (clients.openWindow) {
                    return clients.openWindow(targetUrl);
                }
            })
            .catch(error => {
                console.error('[Service Worker] Error handling notification click:', error);
            })
    );
});

/**
 * Notification close event handler
 * 
 * Triggered when the user dismisses a notification.
 * Useful for analytics or cleanup operations.
 */
self.addEventListener('notificationclose', event => {
    console.log('[Service Worker] Notification dismissed');
    
    // You could send analytics data here if needed
});

/**
 * Push subscription change event handler
 * 
 * Triggered when the push subscription has been changed.
 * This happens when the browser renews the subscription.
 */
self.addEventListener('pushsubscriptionchange', event => {
    console.log('[Service Worker] Push subscription changed');
    
    event.waitUntil(
        self.registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: self.applicationServerKey
        })
        .then(newSubscription => {
            // Send the new subscription details to the server
            console.log('[Service Worker] New subscription:', newSubscription);
            
            return fetch('/notify/api/push-subscription', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    subscription: newSubscription,
                    oldEndpoint: event.oldSubscription ? event.oldSubscription.endpoint : null
                })
            });
        })
    );
});

// Store the application server key for resubscribing if needed
self.addEventListener('message', event => {
    if (event.data && event.data.type === 'SET_VAPID_PUBLIC_KEY') {
        self.applicationServerKey = event.data.key;
        console.log('[Service Worker] Application Server Key stored');
    }
});