<?php

namespace Funlifew\PushNotify\Services;

use Funlifew\PushNotify\Models\Subscription;
use Funlifew\PushNotify\Models\Topic;
use Funlifew\PushNotify\Models\Notification;
use Funlifew\PushNotify\Models\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Client;
use Exception;

class NotificationService
{
    /**
     * Guzzle HTTP client instance.
     *
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * Base URL for the push notification server.
     *
     * @var string
     */
    protected $baseUrl;

    /**
     * Admin token for the push notification server.
     *
     * @var string
     */
    protected $adminToken;

    /**
     * Create a new NotificationService instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->client = new Client();
        $this->baseUrl = config('push-notify.push_base_url');
        $this->adminToken = config('push-notify.push_token');
    }

        /**
     * Send a notification to a single subscription.
     *
     * @param  \Funlifew\PushNotify\Models\Subscription  $subscription
     * @param  string  $title
     * @param  string  $body
     * @param  string|null  $url
     * @param  string|null  $iconPath
     * @return bool|array
     */
    public function sendToSubscription(Subscription $subscription, string $title, string $body, ?string $url = null, ?string $iconPath = null)
    {
        $subscriptionInfo = [
            'endpoint' => $subscription->endpoint,
            'keys' => [
                'auth' => $subscription->auth_key,
                'p256dh' => $subscription->p256dh_key,
            ],
        ];

        try {
            $multipartData = [
                [
                    'name' => 'subscription_info',
                    'contents' => json_encode($subscriptionInfo),
                ],
                [
                    'name' => 'admin_token',
                    'contents' => $this->adminToken,
                ],
                [
                    'name' => 'title',
                    'contents' => $title,
                ],
                [
                    'name' => 'body',
                    'contents' => $body,
                ],
            ];

            // Add URL if provided
            if ($url) {
                $multipartData[] = [
                    'name' => 'url',
                    'contents' => $url,
                ];
            }

            // Add icon if provided
            if ($iconPath && Storage::disk(config('push-notify.storage.disk'))->exists($iconPath)) {
                $filePath = Storage::disk(config('push-notify.storage.disk'))->path($iconPath);
                
                // Make sure the file exists
                if (file_exists($filePath)) {
                    $multipartData[] = [
                        'name' => 'icon',
                        'contents' => fopen($filePath, 'r'),
                        'filename' => basename($filePath),
                        'headers' => ['Content-Type' => $this->getMimeType($filePath)],
                    ];
                } else {
                    Log::warning("Icon file not found at path: {$filePath}");
                }
            }

            // Debug information
            Log::debug('Sending push notification to: ' . $this->baseUrl . 'send/single/');
            Log::debug('Admin Token: ' . (empty($this->adminToken) ? 'EMPTY!' : 'Set'));
            Log::debug('Request data: ' . json_encode(array_map(function($item) {
                return ['name' => $item['name'], 'contents' => $item['name'] === 'admin_token' ? '***' : (is_string($item['contents']) ? $item['contents'] : 'FILE')];
            }, $multipartData)));

            $response = $this->client->request('POST', $this->baseUrl . 'send/single/', [
                'multipart' => $multipartData,
                'debug' => true,
                'timeout' => 30,
                'connect_timeout' => 30,
                'http_errors' => false,
            ]);

            $statusCode = $response->getStatusCode();
            $result = json_decode($response->getBody()->getContents(), true);
            
            Log::debug('Push notification response: ' . $statusCode . ' ' . json_encode($result));

            // Log the notification
            $this->logNotification($subscription->id, null, $title, $body, $url, $iconPath, ($statusCode >= 200 && $statusCode < 300));

            return $result;
        } catch (Exception $e) {
            Log::error('Failed to send notification to subscription: ' . $e->getMessage(), [
                'subscription_id' => $subscription->id,
                'exception' => $e,
                'base_url' => $this->baseUrl,
                'admin_token_set' => !empty($this->adminToken),
            ]);

            // Log the failed notification
            $this->logNotification($subscription->id, null, $title, $body, $url, $iconPath, false, $e->getMessage());

            return false;
        }
    }

    /**
     * Send a notification to a topic.
     *
     * @param  \Funlifew\PushNotify\Models\Topic  $topic
     * @param  string  $title
     * @param  string  $body
     * @param  string|null  $url
     * @param  string|null  $iconPath
     * @return array
     */
    public function sendToTopic(Topic $topic, string $title, string $body, ?string $url = null, ?string $iconPath = null)
    {
        $subscriptions = $topic->subscriptions;
        
        if ($subscriptions->isEmpty()) {
            return [
                'success' => 0,
                'failed' => 0,
                'message' => 'No subscriptions found for this topic'
            ];
        }
        
        $subscriptionInfoList = $subscriptions->map(function ($subscription) {
            return [
                'endpoint' => $subscription->endpoint,
                'keys' => [
                    'auth' => $subscription->auth_key,
                    'p256dh' => $subscription->p256dh_key,
                ],
            ];
        })->toArray();
        
        return $this->sendToMultipleSubscriptions($subscriptionInfoList, $title, $body, $url, $iconPath, $topic->id);
    }

    /**
     * Send a notification to all active subscriptions.
     *
     * @param  string  $title
     * @param  string  $body
     * @param  string|null  $url
     * @param  string|null  $iconPath
     * @return array
     */
    public function sendToAll(string $title, string $body, ?string $url = null, ?string $iconPath = null)
    {
        $subscriptions = Subscription::all();
        
        if ($subscriptions->isEmpty()) {
            return [
                'success' => 0,
                'failed' => 0,
                'message' => 'No subscriptions found'
            ];
        }
        
        $subscriptionInfoList = $subscriptions->map(function ($subscription) {
            return [
                'endpoint' => $subscription->endpoint,
                'keys' => [
                    'auth' => $subscription->auth_key,
                    'p256dh' => $subscription->p256dh_key,
                ],
            ];
        })->toArray();
        
        return $this->sendToMultipleSubscriptions($subscriptionInfoList, $title, $body, $url, $iconPath);
    }

    /**
     * Send a notification to multiple subscriptions.
     *
     * @param  array  $subscriptionInfoList
     * @param  string  $title
     * @param  string  $body
     * @param  string|null  $url
     * @param  string|null  $iconPath
     * @param  int|null  $topicId
     * @return array
     */
    protected function sendToMultipleSubscriptions(array $subscriptionInfoList, string $title, string $body, ?string $url = null, ?string $iconPath = null, ?int $topicId = null)
    {
        try {
            $multipartData = [
                [
                    'name' => 'subscription_info_list',
                    'contents' => json_encode($subscriptionInfoList),
                ],
                [
                    'name' => 'admin_token',
                    'contents' => $this->adminToken,
                ],
                [
                    'name' => 'title',
                    'contents' => $title,
                ],
                [
                    'name' => 'body',
                    'contents' => $body,
                ],
            ];

            // Add URL if provided
            if ($url) {
                $multipartData[] = [
                    'name' => 'url',
                    'contents' => $url,
                ];
            }

            // Add icon if provided
            if ($iconPath && Storage::disk(config('push-notify.storage.disk'))->exists($iconPath)) {
                $filePath = Storage::disk(config('push-notify.storage.disk'))->path($iconPath);
                $multipartData[] = [
                    'name' => 'icon',
                    'contents' => fopen($filePath, 'r'),
                    'filename' => basename($filePath),
                    'headers' => ['Content-Type' => 'image/jpeg'],
                ];
            }

            $response = $this->client->request('POST', $this->baseUrl . 'send/group/', [
                'multipart' => $multipartData,
                'http_errors' => false,
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            // Log notifications (success)
            $subscriptionsByEndpoint = Subscription::all()->keyBy('endpoint');
            
            foreach ($result['success'] ?? [] as $endpoint) {
                if (isset($subscriptionsByEndpoint[$endpoint])) {
                    $subscription = $subscriptionsByEndpoint[$endpoint];
                    $this->logNotification($subscription->id, $topicId, $title, $body, $url, $iconPath, true);
                }
            }
            
            // Log notifications (failed)
            foreach ($result['error'] ?? [] as $endpoint) {
                if (isset($subscriptionsByEndpoint[$endpoint])) {
                    $subscription = $subscriptionsByEndpoint[$endpoint];
                    $this->logNotification(
                        $subscription->id,
                        $topicId,
                        $title,
                        $body,
                        $url,
                        $iconPath,
                        false,
                        'Failed to send notification'
                    );
                }
            }

            return [
                'success' => count($result['success'] ?? []),
                'failed' => count($result['error'] ?? []),
                'result' => $result
            ];
        } catch (Exception $e) {
            Log::error('Failed to send group notification: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return [
                'success' => 0,
                'failed' => count($subscriptionInfoList),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send a notification using a message template.
     *
     * @param  \Funlifew\PushNotify\Models\Message  $message
     * @param  \Funlifew\PushNotify\Models\Subscription|null  $subscription
     * @param  \Funlifew\PushNotify\Models\Topic|null  $topic
     * @param  bool  $sendToAll
     * @return bool|array
     */
    public function sendWithTemplate(Message $message, ?Subscription $subscription = null, ?Topic $topic = null, bool $sendToAll = false)
    {
        if ($subscription) {
            return $this->sendToSubscription(
                $subscription,
                $message->title,
                $message->body,
                $message->url,
                $message->icon_path
            );
        } elseif ($topic) {
            return $this->sendToTopic(
                $topic,
                $message->title,
                $message->body,
                $message->url,
                $message->icon_path
            );
        } elseif ($sendToAll) {
            return $this->sendToAll(
                $message->title,
                $message->body,
                $message->url,
                $message->icon_path
            );
        }
        
        return false;
    }

    /**
     * Schedule a notification for later.
     *
     * @param  array  $data
     * @return \Funlifew\PushNotify\Models\ScheduledNotification
     */
    public function scheduleNotification(array $data)
    {
        return \Funlifew\PushNotify\Models\ScheduledNotification::create($data);
    }
    
    /**
     * Log a notification in the database.
     *
     * @param  int  $subscriptionId
     * @param  int|null  $topicId
     * @param  string  $title
     * @param  string  $body
     * @param  string|null  $url
     * @param  string|null  $iconPath
     * @param  bool  $success
     * @param  string|null  $error
     * @return \Funlifew\PushNotify\Models\Notification
     */
    protected function logNotification(int $subscriptionId, ?int $topicId = null, string $title = null, string $body = null, ?string $url = null, ?string $iconPath = null, bool $success = true, ?string $error = null)
    {
        return Notification::create([
            'subscription_id' => $subscriptionId,
            'topic_id' => $topicId,
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'icon_path' => $iconPath,
            'status' => $success,
            'error' => $error,
        ]);
    }

       /**
     * Get the MIME type of a file.
     *
     * @param string $path
     * @return string
     */
    protected function getMimeType($path)
    {
        $mimeMap = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
        ];
        
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        if (isset($mimeMap[$extension])) {
            return $mimeMap[$extension];
        }
        
        // Try to get mime type using PHP's functions
        if (function_exists('mime_content_type')) {
            return mime_content_type($path);
        }
        
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $path);
            finfo_close($finfo);
            return $mime;
        }
        
        // Default to jpeg if we can't determine the type
        return 'image/jpeg';
    }
}