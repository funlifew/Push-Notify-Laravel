<?php

use Funlifew\PushNotify\Models\Subscription;
use Funlifew\PushNotify\Models\Topic;
use Funlifew\PushNotify\Services\NotificationService;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

if (!function_exists('push_notify_compress_image')) {
    /**
     * Compress and resize an image for use as a notification icon.
     *
     * @param mixed $image The image to compress (file path, uploaded file, or image instance)
     * @param int $width Width to resize to
     * @param int $height Height to resize to
     * @param int $quality Compression quality (0-100)
     * @return string|null The path to the compressed image or null on failure
     */
    function push_notify_compress_image($image, int $width = 64, int $height = 64, int $quality = 75): ?string
    {
        if (!$image) {
            return null;
        }
        
        try {
            $disk = config('push-notify.storage.disk', 'public');
            $path = config('push-notify.storage.icons_path', 'icons');
            
            // Process image with Intervention Image
            if (class_exists('Intervention\Image\Laravel\Facades\Image')) {
                $img = Image::read($image);
                $img->resize($width, $height);
                $img = $img->toJpeg($quality);
                
                $filename = $path . '/' . uniqid() . '.jpg';
                Storage::disk($disk)->put($filename, $img);
                
                return $filename;
            }
            
            // Fallback for when Intervention Image is not available
            if (is_string($image) && file_exists($image)) {
                $extension = pathinfo($image, PATHINFO_EXTENSION);
                $filename = $path . '/' . uniqid() . '.' . $extension;
                Storage::disk($disk)->put($filename, file_get_contents($image));
                return $filename;
            } elseif (method_exists($image, 'store')) {
                return $image->store($path, $disk);
            }
            
            return null;
        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error compressing image: ' . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('push_notify_send')) {
    /**
     * Send a push notification to a subscription, topic, or all subscribers.
     *
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $options Additional options (url, icon, subscription, topic, toAll)
     * @return mixed The result of the send operation
     */
    function push_notify_send(string $title, string $body, array $options = [])
    {
        $notificationService = app(NotificationService::class);
        
        $url = $options['url'] ?? null;
        $iconPath = $options['icon'] ?? null;
        
        // If icon is a file, process it
        if (isset($options['icon']) && !is_string($options['icon'])) {
            $iconPath = push_notify_compress_image($options['icon']);
        }
        
        // Send to subscription
        if (isset($options['subscription'])) {
            $subscription = $options['subscription'];
            
            // If subscription is an ID, get the model
            if (is_numeric($subscription)) {
                $subscription = Subscription::find($subscription);
            }
            
            if ($subscription instanceof Subscription) {
                return $notificationService->sendToSubscription($subscription, $title, $body, $url, $iconPath);
            }
        }
        
        // Send to topic
        if (isset($options['topic'])) {
            $topic = $options['topic'];
            
            // If topic is an ID or slug, get the model
            if (is_numeric($topic)) {
                $topic = Topic::find($topic);
            } elseif (is_string($topic)) {
                $topic = Topic::where('slug', $topic)->first();
            }
            
            if ($topic instanceof Topic) {
                return $notificationService->sendToTopic($topic, $title, $body, $url, $iconPath);
            }
        }
        
        // Send to all
        if (isset($options['toAll']) && $options['toAll']) {
            return $notificationService->sendToAll($title, $body, $url, $iconPath);
        }
        
        throw new InvalidArgumentException('You must specify a subscription, topic, or set toAll option.');
    }
}

if (!function_exists('push_notify_schedule')) {
    /**
     * Schedule a push notification for future delivery.
     *
     * @param string $title Notification title
     * @param string $body Notification body
     * @param string|\DateTime $scheduledAt When to send the notification
     * @param array $options Additional options (url, icon, subscription, topic, toAll)
     * @return \Funlifew\PushNotify\Models\ScheduledNotification
     */
    function push_notify_schedule(string $title, string $body, $scheduledAt, array $options = [])
    {
        $notificationService = app(NotificationService::class);
        
        $scheduledAt = $scheduledAt instanceof \DateTime
            ? \Illuminate\Support\Carbon::instance($scheduledAt)
            : \Illuminate\Support\Carbon::parse($scheduledAt);
        
        $url = $options['url'] ?? null;
        $iconPath = $options['icon'] ?? null;
        
        // If icon is a file, process it
        if (isset($options['icon']) && !is_string($options['icon'])) {
            $iconPath = push_notify_compress_image($options['icon']);
        }
        
        $data = [
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'icon_path' => $iconPath,
            'scheduled_at' => $scheduledAt,
            'status' => 'pending',
            'created_by' => auth()->id(),
        ];
        
        // Set recipient
        if (isset($options['subscription'])) {
            $subscription = $options['subscription'];
            
            // If subscription is an ID, use that directly
            if (is_numeric($subscription)) {
                $data['subscription_id'] = $subscription;
            } elseif ($subscription instanceof Subscription) {
                $data['subscription_id'] = $subscription->id;
            }
        } elseif (isset($options['topic'])) {
            $topic = $options['topic'];
            
            // If topic is an ID, use that directly
            if (is_numeric($topic)) {
                $data['topic_id'] = $topic;
            } elseif (is_string($topic)) {
                $topic = Topic::where('slug', $topic)->first();
                if ($topic) {
                    $data['topic_id'] = $topic->id;
                }
            } elseif ($topic instanceof Topic) {
                $data['topic_id'] = $topic->id;
            }
        } elseif (isset($options['toAll']) && $options['toAll']) {
            $data['send_to_all'] = true;
        } else {
            throw new InvalidArgumentException('You must specify a subscription, topic, or set toAll option.');
        }
        
        // If message_id is provided, use that instead of title/body/url/icon
        if (isset($options['message_id'])) {
            $data['message_id'] = $options['message_id'];
            unset($data['title'], $data['body'], $data['url'], $data['icon_path']);
        }
        
        return $notificationService->scheduleNotification($data);
    }
}

if (!function_exists('push_notify_get_subscriptions_count')) {
    /**
     * Get the count of active subscriptions.
     *
     * @param array $options Filter options (userId, topic)
     * @return int
     */
    function push_notify_get_subscriptions_count(array $options = []): int
    {
        $query = Subscription::query();
        
        // Filter by user ID
        if (isset($options['userId'])) {
            $query->where('user_id', $options['userId']);
        }
        
        // Filter by topic
        if (isset($options['topic'])) {
            $topic = $options['topic'];
            
            if (is_numeric($topic)) {
                $query->whereHas('topics', function ($q) use ($topic) {
                    $q->where('topics.id', $topic);
                });
            } elseif (is_string($topic)) {
                $query->whereHas('topics', function ($q) use ($topic) {
                    $q->where('topics.slug', $topic);
                });
            } elseif ($topic instanceof Topic) {
                $query->whereHas('topics', function ($q) use ($topic) {
                    $q->where('topics.id', $topic->id);
                });
            }
        }
        
        return $query->count();
    }
}

if (!function_exists('push_notify_is_user_subscribed')) {
    /**
     * Check if a user is subscribed to push notifications.
     *
     * @param int|null $userId The user ID to check (defaults to current authenticated user)
     * @return bool
     */
    function push_notify_is_user_subscribed(?int $userId = null): bool
    {
        if ($userId === null && auth()->check()) {
            $userId = auth()->id();
        }
        
        if (!$userId) {
            return false;
        }
        
        return Subscription::where('user_id', $userId)->exists();
    }
}

if (!function_exists('push_notify_meta_tag')) {
    /**
     * Generate HTML meta tag with VAPID public key.
     *
     * @return string HTML meta tag
     */
    function push_notify_meta_tag(): string
    {
        $publicKey = config('push-notify.public_vapid_key');
        return '<meta name="vapid-public-key" content="' . $publicKey . '">';
    }
}

if (!function_exists('push_notify_subscription_script')) {
    /**
     * Generate HTML script tag for the subscription script.
     *
     * @param bool $includeInit Whether to include initialization code
     * @param array $options Configuration options
     * @return string HTML script tags
     */
    function push_notify_subscription_script(bool $includeInit = false, array $options = []): string
    {
        $html = '<script src="' . asset('vendor/push-notify/js/subscription.js') . '"></script>';
        
        if ($includeInit) {
            $defaultOptions = [
                'debug' => config('app.debug', false),
                'applicationServerKey' => config('push-notify.public_vapid_key'),
            ];
            
            $mergedOptions = array_merge($defaultOptions, $options);
            $optionsJson = json_encode($mergedOptions);
            
            $html .= '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    window.PushNotify.init(' . $optionsJson . ');
                });
            </script>';
        }
        
        return $html;
    }
}