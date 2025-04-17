/**
 * Push Notification Subscription Handler
 * 
 * This script manages the subscription process for web push notifications.
 * It provides functions to request permission, subscribe, and send subscription details to the server.
 */

'use strict';

// Default configuration
const DEFAULT_CONFIG = {
    serverUrl: '/notify/api/push-subscription',
    applicationServerKey: null, // Will be populated from meta tag or config
    serviceWorkerPath: '/sw.js',
    debug: false,
    requestPermissionOnPageLoad: false,
    showPermissionDialog: true,
    permissionDialog: {
        title: 'Enable Notifications',
        body: 'Would you like to receive notifications from this site?',
        allowButtonText: 'Allow',
        denyButtonText: 'Not Now',
        allowButtonClass: 'btn btn-primary',
        denyButtonClass: 'btn btn-secondary',
        dialogClass: 'push-notify-dialog'
    },
    callbacks: {
        onPermissionChange: null,
        onSubscriptionChange: null,
        onSuccess: null,
        onError: null
    }
};

// Global configuration
let config = { ...DEFAULT_CONFIG };

/**
 * Initialize the push notification system
 * 
 * @param {Object} userConfig - Configuration options to override defaults
 * @returns {Promise<boolean>} - Whether initialization was successful
 */
function initializePushNotify(userConfig = {}) {
    // Merge user config with defaults
    config = { ...DEFAULT_CONFIG, ...userConfig };
    
    // If no application server key provided, look for it in meta tag
    if (!config.applicationServerKey) {
        const metaTag = document.querySelector('meta[name="vapid-public-key"]');
        if (metaTag) {
            config.applicationServerKey = metaTag.content;
        }
    }
    
    // Validate the config
    if (!config.applicationServerKey) {
        logError('No application server key provided. You must set it via config or meta tag.');
        return Promise.resolve(false);
    }
    
    // Check if push notifications are supported
    if (!isPushSupported()) {
        logError('Push notifications are not supported in this browser.');
        return Promise.resolve(false);
    }
    
    // Initialize request permission on page load if configured
    if (config.requestPermissionOnPageLoad) {
        return requestPermission();
    }
    
    return Promise.resolve(true);
}

/**
 * Check if push notifications are supported in this browser
 * 
 * @returns {boolean} - Whether push is supported
 */
function isPushSupported() {
    return 'serviceWorker' in navigator && 'PushManager' in window;
}

/**
 * Request notification permission
 * 
 * @returns {Promise<boolean>} - Whether permission was granted
 */
function requestPermission() {
    // If permission already granted, resolve immediately
    if (Notification.permission === 'granted') {
        logDebug('Permission already granted.');
        return Promise.resolve(true);
    }
    
    // If permission was denied, we can't ask again
    if (Notification.permission === 'denied') {
        logDebug('Permission was previously denied.');
        return Promise.resolve(false);
    }
    
    // Show permission dialog if configured
    if (config.showPermissionDialog) {
        return showPermissionDialog()
            .then(dialogResult => {
                if (!dialogResult) {
                    return false;
                }
                return requestSystemPermission();
            });
    }
    
    // Otherwise, request permission directly
    return requestSystemPermission();
}

/**
 * Request system notification permission
 * 
 * @returns {Promise<boolean>} - Whether permission was granted
 */
function requestSystemPermission() {
    return Notification.requestPermission()
        .then(permission => {
            const granted = permission === 'granted';
            
            // Callback if provided
            if (typeof config.callbacks.onPermissionChange === 'function') {
                config.callbacks.onPermissionChange(granted);
            }
            
            return granted;
        });
}

/**
 * Show a custom permission dialog before requesting system permission
 * 
 * @returns {Promise<boolean>} - Whether the user clicked "Allow"
 */
function showPermissionDialog() {
    return new Promise(resolve => {
        // Create dialog container
        const dialog = document.createElement('div');
        dialog.className = config.permissionDialog.dialogClass;
        dialog.style.position = 'fixed';
        dialog.style.top = '0';
        dialog.style.left = '0';
        dialog.style.right = '0';
        dialog.style.bottom = '0';
        dialog.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
        dialog.style.zIndex = '9999';
        dialog.style.display = 'flex';
        dialog.style.alignItems = 'center';
        dialog.style.justifyContent = 'center';
        
        // Create dialog content
        const content = document.createElement('div');
        content.style.backgroundColor = '#fff';
        content.style.padding = '20px';
        content.style.borderRadius = '8px';
        content.style.maxWidth = '400px';
        content.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.1)';
        
        // Title
        const title = document.createElement('h3');
        title.textContent = config.permissionDialog.title;
        title.style.marginTop = '0';
        
        // Body
        const body = document.createElement('p');
        body.textContent = config.permissionDialog.body;
        
        // Buttons container
        const buttons = document.createElement('div');
        buttons.style.display = 'flex';
        buttons.style.justifyContent = 'flex-end';
        buttons.style.marginTop = '20px';
        
        // Allow button
        const allowButton = document.createElement('button');
        allowButton.textContent = config.permissionDialog.allowButtonText;
        allowButton.className = config.permissionDialog.allowButtonClass;
        allowButton.style.marginLeft = '10px';
        
        // Deny button
        const denyButton = document.createElement('button');
        denyButton.textContent = config.permissionDialog.denyButtonText;
        denyButton.className = config.permissionDialog.denyButtonClass;
        
        // Append elements
        buttons.appendChild(denyButton);
        buttons.appendChild(allowButton);
        content.appendChild(title);
        content.appendChild(body);
        content.appendChild(buttons);
        dialog.appendChild(content);
        document.body.appendChild(dialog);
        
        // Event listeners
        allowButton.addEventListener('click', () => {
            document.body.removeChild(dialog);
            resolve(true);
        });
        
        denyButton.addEventListener('click', () => {
            document.body.removeChild(dialog);
            resolve(false);
        });
    });
}

/**
 * Subscribe to push notifications
 * 
 * @param {Object} options - Additional options
 * @param {number|string} options.userId - User ID to associate with subscription
 * @param {Array<string>} options.topics - Topics to subscribe to
 * @returns {Promise<boolean>} - Whether subscription was successful
 */
function subscribeToPush(options = {}) {
    // Request permission first
    return requestPermission()
        .then(granted => {
            if (!granted) {
                logDebug('Permission not granted.');
                return false;
            }
            
            // Register service worker
            return registerServiceWorker()
                .then(registration => {
                    // Convert application server key to Uint8Array
                    const applicationServerKey = urlBase64ToUint8Array(config.applicationServerKey);
                    
                    // Pass the key to the service worker for potential resubscription
                    if (registration.active) {
                        registration.active.postMessage({
                            type: 'SET_VAPID_PUBLIC_KEY',
                            key: applicationServerKey
                        });
                    }
                    
                    // Check for existing subscription
                    return registration.pushManager.getSubscription()
                        .then(subscription => {
                            if (subscription) {
                                logDebug('Already subscribed, updating subscription');
                                return subscription;
                            }
                            
                            // Create new subscription
                            return registration.pushManager.subscribe({
                                userVisibleOnly: true,
                                applicationServerKey: applicationServerKey
                            });
                        })
                        .then(subscription => {
                            // Send subscription to server
                            return sendSubscriptionToServer(subscription, options);
                        });
                });
        })
        .catch(error => {
            logError('Error subscribing to push:', error);
            
            // Callback if provided
            if (typeof config.callbacks.onError === 'function') {
                config.callbacks.onError(error);
            }
            
            return false;
        });
}

/**
 * Register the service worker
 * 
 * @returns {Promise<ServiceWorkerRegistration>} - Service worker registration
 */
function registerServiceWorker() {
    return navigator.serviceWorker.register(config.serviceWorkerPath)
        .then(registration => {
            logDebug('Service worker registered:', registration);
            
            // Wait for the service worker to become active
            if (registration.installing) {
                // Service worker is still installing
                return new Promise(resolve => {
                    registration.installing.addEventListener('statechange', () => {
                        if (registration.active) {
                            resolve(registration);
                        }
                    });
                });
            }
            
            return registration;
        });
}

/**
 * Send subscription details to the server
 * 
 * @param {PushSubscription} subscription - The subscription object
 * @param {Object} options - Additional subscription options
 * @returns {Promise<boolean>} - Whether sending was successful
 */
function sendSubscriptionToServer(subscription, options = {}) {
    // Convert keys to Base64
    const rawKey = subscription.getKey ? subscription.getKey('p256dh') : '';
    const key = rawKey ? btoa(String.fromCharCode.apply(null, new Uint8Array(rawKey))) : '';
    
    const rawAuth = subscription.getKey ? subscription.getKey('auth') : '';
    const auth = rawAuth ? btoa(String.fromCharCode.apply(null, new Uint8Array(rawAuth))) : '';
    
    // Prepare data to send
    const data = {
        subscription: {
            endpoint: subscription.endpoint,
            keys: {
                p256dh: key,
                auth: auth
            }
        },
        user_id: options.userId || null,
        topics: options.topics || []
    };
    
    // Send to server
    return fetch(config.serverUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Server returned ' + response.status);
        }
        return response.json();
    })
    .then(responseData => {
        logDebug('Subscription sent successfully:', responseData);
        
        // Store subscription in local storage
        localStorage.setItem('pushSubscription', JSON.stringify(subscription));
        
        // Callback if provided
        if (typeof config.callbacks.onSuccess === 'function') {
            config.callbacks.onSuccess(subscription, responseData);
        }
        
        // Callback for subscription change
        if (typeof config.callbacks.onSubscriptionChange === 'function') {
            config.callbacks.onSubscriptionChange(subscription);
        }
        
        return true;
    })
    .catch(error => {
        logError('Error sending subscription to server:', error);
        
        // Callback if provided
        if (typeof config.callbacks.onError === 'function') {
            config.callbacks.onError(error);
        }
        
        return false;
    });
}

/**
 * Unsubscribe from push notifications
 * 
 * @returns {Promise<boolean>} - Whether unsubscription was successful
 */
function unsubscribeFromPush() {
    return navigator.serviceWorker.ready
        .then(registration => {
            return registration.pushManager.getSubscription();
        })
        .then(subscription => {
            if (!subscription) {
                logDebug('No subscription to unsubscribe from.');
                return true;
            }
            
            // Store endpoint before unsubscribing
            const endpoint = subscription.endpoint;
            
            // Unsubscribe
            return subscription.unsubscribe()
                .then(successful => {
                    if (successful) {
                        // Remove from local storage
                        localStorage.removeItem('pushSubscription');
                        
                        // Notify server about unsubscription
                        return fetch(config.serverUrl + '/unsubscribe', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ endpoint })
                        })
                        .then(response => {
                            if (!response.ok) {
                                logError('Server returned error when unsubscribing:', response.status);
                            }
                            
                            // Callback for subscription change
                            if (typeof config.callbacks.onSubscriptionChange === 'function') {
                                config.callbacks.onSubscriptionChange(null);
                            }
                            
                            return true;
                        })
                        .catch(error => {
                            logError('Error notifying server about unsubscription:', error);
                            return true; // Still consider unsubscription successful
                        });
                    }
                    
                    return false;
                });
        })
        .catch(error => {
            logError('Error unsubscribing:', error);
            return false;
        });
}

/**
 * Convert a URL-safe base64 string to a Uint8Array
 * 
 * @param {string} base64String - URL-safe base64 encoded string
 * @returns {Uint8Array} - Decoded array
 */
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/-/g, '+')
        .replace(/_/g, '/');
    
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    
    return outputArray;
}

/**
 * Log debug messages if debug mode is enabled
 * 
 * @param {...any} args - Arguments to log
 */
function logDebug(...args) {
    if (config.debug) {
        console.log('[PushNotify]', ...args);
    }
}

/**
 * Log error messages
 * 
 * @param {...any} args - Arguments to log
 */
function logError(...args) {
    console.error('[PushNotify]', ...args);
}

/**
 * Main function to handle push notification subscription
 * 
 * @param {number|string} userId - User ID to associate with subscription
 * @param {Array<string>} topics - Topics to subscribe to
 * @param {Object} userConfig - Configuration options
 * @returns {Promise<boolean>} - Whether subscription was successful
 */
function handleSubscription(userId = null, topics = [], userConfig = {}) {
    // Initialize with user config
    return initializePushNotify(userConfig)
        .then(initialized => {
            if (!initialized) {
                return false;
            }
            
            // Subscribe to push notifications
            return subscribeToPush({ userId, topics });
        });
}

/**
 * Check subscription status
 * 
 * @returns {Promise<Object>} - Subscription status
 */
function checkSubscriptionStatus() {
    if (!isPushSupported()) {
        return Promise.resolve({
            supported: false,
            subscribed: false,
            permission: Notification.permission
        });
    }
    
    return navigator.serviceWorker.ready
        .then(registration => {
            return registration.pushManager.getSubscription();
        })
        .then(subscription => {
            return {
                supported: true,
                subscribed: !!subscription,
                permission: Notification.permission,
                subscription: subscription
            };
        })
        .catch(error => {
            logError('Error checking subscription status:', error);
            return {
                supported: true,
                subscribed: false,
                permission: Notification.permission,
                error: error.message
            };
        });
}

// Export functions
window.PushNotify = {
    init: initializePushNotify,
    subscribe: subscribeToPush,
    unsubscribe: unsubscribeFromPush,
    handleSubscription: handleSubscription,
    requestPermission: requestPermission,
    checkStatus: checkSubscriptionStatus,
    isSupported: isPushSupported
};