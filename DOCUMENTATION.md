# Push-Notify

A comprehensive Laravel package for web push notifications with topic-based subscriptions, scheduling capabilities, and admin dashboard. Developed for internal use at our organization to integrate with our Django push notification server.

![Push-Notify](https://via.placeholder.com/1200x400?text=Push+Notify+Laravel+Client)

## Overview

Push-Notify provides a complete solution for implementing web push notifications in Laravel applications. It handles subscription management, topic-based notifications, scheduled notifications, and provides an intuitive admin dashboard for managing all aspects of your push notification system.

The package works with a companion Django Push Notification Server for handling the actual delivery of notifications. This package is designed for internal use within our organization and works seamlessly with our existing Django push notification infrastructure.

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
- Access to our internal Django Push Notification Server
- Composer with access to our private repositories
- GuzzleHTTP for API communication
- Optional: Intervention/Image for icon processing

### Django Push Server Requirements

To use this package, you need access to our Django push notification server with:

- Proper VAPID keys configuration
- Admin token for authentication
- Network accessibility from your Laravel application

## Installation

Since this package is hosted in a private repository, you'll need to install it directly from GitHub:

### 1. Add the repository to your project's composer.json:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/funlifew/push-notify.git"
        }
    ],
    "require": {
        "funlifew/push-notify": "dev-main"
    }
}
```

Then run:

```bash
composer update
```

### 2. Install the package:

```bash
php artisan push:install
```

This command will:
- Publish assets (service worker, subscription script)
- Publish configuration file
- Run migrations
- Create storage directories
- Generate default icons and pages

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

### 4. Set up the service worker:

The installation process will automatically copy the service worker file (`sw.js`) to your public directory. Make sure it's accessible at the root of your domain.

### 5. Add the scheduler to your crontab (for scheduled notifications):

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

## Basic Usage

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
use Funlifew\PushNotify\Facades\PushNotify;

// Create a message template
$template = Message::create([
    'title' => 'Welcome!',
    'body' => 'Thank you for subscribing to our notifications.',
    'url' => 'https://example.com/welcome',
]);

// Send using a template
PushNotify::sendWithTemplate($template, null, $topic);
```

## JavaScript Client API

The package provides a JavaScript API that can be used on the client side:

```javascript
// Initialize with options
window.PushNotify.init({
    debug: true,
    applicationServerKey: 'your-vapid-public-key',
    callbacks: {
        onSuccess: function(subscription) {
            console.log('Subscribed successfully', subscription);
        }
    }
});

// Subscribe a user
window.PushNotify.handleSubscription(
    userId,   // Optional user ID
    ['news', 'updates']  // Optional topics
);

// Check subscription status
window.PushNotify.checkStatus().then(function(status) {
    console.log('Subscription status:', status);
});

// Unsubscribe
window.PushNotify.unsubscribe().then(function(result) {
    console.log('Unsubscribed:', result);
});
```

## Admin Dashboard

The package provides an admin dashboard at `/notify` where you can:

- View and manage subscriptions
- Create and manage topics
- Send notifications to individual subscribers, topics, or all users
- Create and use message templates
- Schedule notifications for future delivery
- View notification history and statistics

## Available Routes

| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | notify | notify.dashboard | Dashboard overview |
| GET | notify/subscriptions | notify.subscriptions.index | List all subscriptions |
| GET | notify/subscriptions/{id}/send | notify.subscriptions.send | Show form to send to specific subscription |
| POST | notify/subscriptions/{id}/send | notify.subscriptions.send.post | Send to specific subscription |
| DELETE | notify/subscriptions/{id} | notify.subscriptions.destroy | Delete a subscription |
| GET | notify/subscriptions/send-all | notify.subscriptions.send-all | Show form to send to all subscriptions |
| POST | notify/subscriptions/send-all | notify.subscriptions.send-all.post | Send to all subscriptions |
| GET | notify/topics | notify.topics.index | List all topics |
| GET | notify/topics/create | notify.topics.create | Show form to create a topic |
| POST | notify/topics | notify.topics.store | Create a new topic |
| GET | notify/topics/{id}/edit | notify.topics.edit | Show form to edit a topic |
| PUT | notify/topics/{id} | notify.topics.update | Update a topic |
| DELETE | notify/topics/{id} | notify.topics.destroy | Delete a topic |
| GET | notify/topics/{id}/send | notify.topics.send | Show form to send to a topic |
| POST | notify/topics/{id}/send | notify.topics.send.post | Send to a topic |
| GET | notify/messages | notify.messages.index | List all message templates |
| GET | notify/messages/create | notify.messages.create | Show form to create a message template |
| POST | notify/messages | notify.messages.store | Create a new message template |
| GET | notify/messages/{id}/edit | notify.messages.edit | Show form to edit a message template |
| PUT | notify/messages/{id} | notify.messages.update | Update a message template |
| DELETE | notify/messages/{id} | notify.messages.destroy | Delete a message template |
| GET | notify/scheduled | notify.scheduled.index | List all scheduled notifications |
| GET | notify/scheduled/create | notify.scheduled.create | Show form to create a scheduled notification |
| POST | notify/scheduled | notify.scheduled.store | Create a new scheduled notification |
| GET | notify/scheduled/{id} | notify.scheduled.show | Show a scheduled notification |
| GET | notify/scheduled/{id}/edit | notify.scheduled.edit | Show form to edit a scheduled notification |
| PUT | notify/scheduled/{id} | notify.scheduled.update | Update a scheduled notification |
| POST | notify/scheduled/{id}/send-now | notify.scheduled.send-now | Send a scheduled notification immediately |
| DELETE | notify/scheduled/{id} | notify.scheduled.cancel | Cancel a scheduled notification |
| POST | notify/api/push-subscription | push-notify.api.subscribe | API endpoint for creating subscriptions |
| POST | notify/api/push-subscription/unsubscribe | push-notify.api.unsubscribe | API endpoint for removing subscriptions |

## Database Schema

The package creates the following tables:

### subscriptions
- id
- user_id (nullable, foreign key to users table)
- endpoint (unique URL for the subscription)
- auth_key
- p256dh_key
- device (browser/device info)
- os (operating system)
- last_used_at (timestamp)
- ip_address
- created_at
- updated_at

### topics
- id
- name
- slug (unique)
- created_at
- updated_at

### subscription_topic (pivot table)
- id
- subscription_id (foreign key)
- topic_id (foreign key)
- created_at
- updated_at

### messages
- id
- title
- body
- url (nullable)
- icon_path (nullable)
- created_at
- updated_at

### notifications
- id
- subscription_id (foreign key)
- topic_id (nullable, foreign key)
- status (boolean)
- title (nullable)
- body (nullable)
- icon_path (nullable)
- url (nullable)
- message_id (nullable, foreign key)
- created_at
- updated_at

### scheduled_notifications
- id
- subscription_id (nullable, foreign key)
- topic_id (nullable, foreign key)
- send_to_all (boolean)
- message_id (nullable, foreign key)
- title (nullable)
- body (nullable)
- url (nullable)
- icon_path (nullable)
- scheduled_at (timestamp)
- sent_at (nullable, timestamp)
- status (enum: pending, processing, sent, failed)
- error (nullable)
- attempts (integer)
- created_by (nullable, foreign key to users table)
- created_at
- updated_at

## Available Helper Functions

- `push_notify_compress_image($image, $width, $height, $quality)` - Compresses and resizes an image
- `push_notify_send($title, $body, $options)` - Sends a push notification
- `push_notify_schedule($title, $body, $scheduledAt, $options)` - Schedules a push notification
- `push_notify_get_subscriptions_count($options)` - Gets count of active subscriptions
- `push_notify_is_user_subscribed($userId)` - Checks if a user is subscribed
- `push_notify_meta_tag()` - Generates HTML meta tag with VAPID public key
- `push_notify_subscription_script($includeInit, $options)` - Generates subscription script HTML

## Artisan Commands

| Command | Description |
|---------|-------------|
| `php artisan push:install` | Install the Push Notify package |
| `php artisan push-token:generate` | Generate an admin token for the push server |
| `php artisan push:send-scheduled` | Process and send due scheduled notifications |

## Working with Topics

Topics allow you to group subscribers for targeted notifications:

```php
// Create a topic
$topic = \Funlifew\PushNotify\Models\Topic::create([
    'name' => 'Product Updates',
    'slug' => 'product-updates'
]);

// Add subscriptions to a topic
$topic->subscriptions()->attach($subscription->id);

// Send to a topic
PushNotify::send('New Feature', 'We just launched a new feature!', [
    'topic' => 'product-updates'
]);
```

## Service Worker Customization

The service worker (`sw.js`) handles the actual display of notifications on the client side. You can customize it after installation to change notification behavior, add offline support, or implement caching strategies.

## Troubleshooting

Common issues and their solutions:

1. **Notifications not working**
   - Ensure your Push Server is properly configured
   - Check that VAPID keys match between server and client
   - Verify that the service worker is registered correctly

2. **Permission denied errors**
   - Notifications require explicit user permission
   - Make sure to request permission at an appropriate time in your application flow

3. **Missing icons**
   - Check storage permissions
   - Verify that the storage/app/public/icons directory exists and is writable

4. **Scheduled notifications not sending**
   - Verify your Laravel scheduler is running
   - Check the database for error messages in scheduled_notifications table

## Security Considerations

- The admin dashboard should be protected by authentication middleware
- VAPID keys should be kept secure
- User subscription data should be treated as sensitive information

## Credits

This package was created by Mehdi Radfar and is licensed under the MIT License.

## License

The Push-Notify package is open-sourced software licensed under the [MIT license](LICENSE.md).