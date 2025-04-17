# Push-Notify

A comprehensive Laravel package for web push notifications with topic-based subscriptions, scheduling capabilities, and admin dashboard.

## Overview

Push-Notify provides a complete solution for implementing web push notifications in Laravel applications. It handles subscription management, topic-based notifications, scheduled notifications, and provides an intuitive admin dashboard for managing all aspects of your push notification system.

The package works with a companion Django Push Notification Server for handling the actual delivery of notifications.

## Features

- 🔔 **Web Push Notifications**: Send push notifications to browsers that support the Web Push API
- 👥 **Topic Support**: Group subscribers into topics for targeted notifications
- ⏰ **Scheduled Notifications**: Schedule notifications to be sent at a future date and time
- 📱 **Device Detection**: Track device information for subscriptions
- 🔄 **Message Templates**: Create and reuse notification templates
- 📊 **Admin Dashboard**: Manage subscriptions, topics, and notifications through a clean interface

## Requirements

- PHP 8.1+
- Laravel 9.0+
- MySQL/PostgreSQL/SQLite
- [Push Notification Server](https://github.com/funlifew/push-notification-server) - Django REST server for handling notification delivery

## Installation

### 1. Require the package via Composer:

```bash
composer require funlifew/push-notify
```

### 2. Publish assets and run migrations:

```bash
php artisan push:install
```

### 3. Configure your environment:

Add the following to your `.env` file:

```
PUSH_BASE_URL=https://your-push-server.com/api/push/
PUSH_TOKEN=your_admin_token
VAPID_PUBLIC_KEY=your_vapid_public_key
```

To generate a new admin token, run:

```bash
php artisan push-token:generate
```

### 4. Add service worker to your public directory:

The installation process will automatically copy the service worker file (`sw.js`) to your public directory. Make sure it's accessible at the root of your domain.

## Usage

### 1. Include subscription script in your layout:

```php
{!! push_notify_subscription_script(true) !!}
```

### 2. Add a button to trigger subscription:

```html
<button onclick="window.PushNotify.handleSubscription()">Subscribe to Notifications</button>
```

### 3. Subscribe a user to a specific topic:

```html
<button onclick="window.PushNotify.handleSubscription({{ Auth::id() }}, ['news', 'updates'])">
    Subscribe to News & Updates
</button>
```

### 4. Access the admin dashboard:

Visit `/notify` to access the admin dashboard where you can manage subscriptions, topics, and send notifications.

## Sending Notifications from Your Code

### Sending a Simple Notification

```php
use Funlifew\PushNotify\Facades\PushNotify;

// Send to a specific subscription
PushNotify::send('Notification Title', 'Notification Body', [
    'subscription' => $subscription,
    'url' => 'https://example.com/notification-target',
]);

// Send to a topic
PushNotify::send('Notification Title', 'Notification Body', [
    'topic' => 'news',
    'url' => 'https://example.com/news',
]);

// Send to all subscribers
PushNotify::send('Notification Title', 'Notification Body', [
    'toAll' => true,
]);
```

### Scheduling a Notification

```php
use Funlifew\PushNotify\Facades\PushNotify;

// Schedule for a specific time
PushNotify::schedule(
    'Sale Starting Soon!', 
    'Our annual sale begins tomorrow!', 
    now()->addDays(1), 
    [
        'topic' => 'promotions',
        'url' => 'https://example.com/sale',
    ]
);
```

### Using Message Templates

```php
use Funlifew\PushNotify\Models\Message;

// Create a message template
$template = Message::create([
    'title' => 'Welcome!',
    'body' => 'Thank you for subscribing to our notifications.',
    'url' => 'https://example.com/welcome',
]);

// Send using a template
PushNotify::sendWithTemplate($template, null, $topic);
```

## Scheduling Notifications

1. Navigate to the admin dashboard at `/notify`
2. Select "Scheduled Notifications" from the sidebar
3. Click "Schedule New"
4. Fill in notification details (title, body, URL, icon)
5. Select recipient (all subscribers, topic, or single subscription)
6. Set date and time
7. Click "Schedule Notification"

Make sure to set up a cron job to run Laravel's scheduler:

```
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## Configuration

After publishing the configuration files, you can modify settings in `config/push-notify.php`:

```php
return [
    // Admin token for the push server
    'push_token' => env('PUSH_TOKEN', null),
    
    // Base URL of the push server
    'push_base_url' => env('PUSH_BASE_URL', 'http://localhost:8000/api/push/'),
    
    // Default icon for notifications
    'default_icon' => '/icon.png',
    
    // Auto-subscribe users on login
    'auto_subscribe' => false,
    
    // Track device information
    'track_devices' => true,
    
    // Public VAPID key - this needs to match your push server
    'public_vapid_key' => env('VAPID_PUBLIC_KEY'),
    
    // Routes configuration
    'routes' => [
        // The URI prefix for all routes
        'prefix' => 'notify',
        
        // Admin route middleware
        'admin_middleware' => ['web', 'auth'],
    ],
    
    // Storage settings for notification icons
    'storage' => [
        'disk' => 'public',
        'icons_path' => 'icons',
        'image_quality' => 75,
    ],
];
```

## Available Helper Functions

- `push_notify_compress_image($image, $width, $height, $quality)` - Compresses and resizes an image
- `push_notify_send($title, $body, $options)` - Sends a push notification
- `push_notify_schedule($title, $body, $scheduledAt, $options)` - Schedules a push notification
- `push_notify_get_subscriptions_count($options)` - Gets count of active subscriptions
- `push_notify_is_user_subscribed($userId)` - Checks if a user is subscribed
- `push_notify_meta_tag()` - Generates HTML meta tag with VAPID public key
- `push_notify_subscription_script($includeInit, $options)` - Generates subscription script HTML

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).