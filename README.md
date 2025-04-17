# PushNotify

A Laravel package for web push notifications with topic support and scheduling features.

## Overview

PushNotify is a comprehensive solution for implementing web push notifications in Laravel applications. It handles subscription management, topic-based notifications, scheduled notifications, and provides an admin interface for managing everything.

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
```

To generate a new admin token, run:

```bash
php artisan push-token:generate
```

### 4. Add service worker to your public directory:

The installation process will automatically copy the service worker file (`sw.js`) to your public directory. Make sure it's accessible at the root of your domain.

## Usage

### 1. Include subscription script in your layout:

```html
<script src="{{ asset('vendor/pushnotify/js/subscription.js') }}"></script>
```

### 2. Add a button to trigger subscription:

```html
<button onclick="handleSubscription()">Subscribe to Notifications</button>
```

### 3. Subscribe a user to a specific topic:

```html
<button onclick="handleSubscription({{ Auth::id() }}, ['news', 'updates'])">
    Subscribe to News & Updates
</button>
```

### 4. Access the admin dashboard:

Visit `/notify` to access the admin dashboard where you can manage subscriptions, topics, and send notifications.

## Scheduling Notifications

1. Navigate to the admin dashboard at `/notify`
2. Select "Send Notification" 
3. Fill in notification details (title, body, URL, icon)
4. Check the "Schedule for later" option
5. Select date and time
6. Click "Schedule"

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
];
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).