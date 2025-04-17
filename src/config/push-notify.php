<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Push Server Configuration
    |--------------------------------------------------------------------------
    |
    | These settings are required to connect to your push notification server.
    | The server uses VAPID keys for authenticating push requests.
    |
    */

    // Admin token for the push server
    'push_token' => env('PUSH_TOKEN', null),
    
    // Base URL of the push server
    'push_base_url' => env('PUSH_BASE_URL', 'http://localhost:8000/api/push/'),
    
    /*
    |--------------------------------------------------------------------------
    | Notification Defaults
    |--------------------------------------------------------------------------
    |
    | Default settings for notifications if not specified
    |
    */
    
    // Default icon for notifications
    'default_icon' => '/icon.png',
    
    // Should notifications require interaction by default?
    'require_interaction' => true,
    
    /*
    |--------------------------------------------------------------------------
    | Subscription Settings
    |--------------------------------------------------------------------------
    |
    | Configure how subscriptions are handled
    |
    */
    
    // Auto-subscribe users on login
    'auto_subscribe' => false,
    
    // Track device information 
    'track_devices' => true,
    
    // Public VAPID key - this needs to match your push server
    'public_vapid_key' => env('VAPID_PUBLIC_KEY', 'BMDYzPNesM5nLtKmrId10axXSS8krjM8rNs18-HQtDLobgZMXbAV3wgnYnGKgy11WcC8V25e3Q7UNyJ2UK2aFwg'),
    
    /*
    |--------------------------------------------------------------------------
    | Routes Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the routes used by this package
    |
    */
    
    'routes' => [
        // The URI prefix for all routes
        'prefix' => 'notify',
        
        // Enable or disable CSRF protection for the API routes
        'disable_csrf' => true,
        
        // Apply middleware to all routes (except those that explicitly disable it)
        'middleware' => ['web'],
        
        // Admin route middleware
        'admin_middleware' => ['web', 'auth'],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Storage Settings
    |--------------------------------------------------------------------------
    |
    | Configure where and how notification icons are stored
    |
    */
    
    'storage' => [
        // The disk to store notification icons
        'disk' => 'public',
        
        // The path within the disk to store icons
        'icons_path' => 'icons',
        
        // Image compression quality (0-100)
        'image_quality' => 75,
        
        // Icon dimensions
        'icon_width' => 64,
        'icon_height' => 64,
    ],
];