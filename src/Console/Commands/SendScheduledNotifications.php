<?php

namespace Funlifew\PushNotify\Console\Commands;

use Illuminate\Console\Command;
use Funlifew\PushNotify\Models\ScheduledNotification;
use Funlifew\PushNotify\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class SendScheduledNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'push:send-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send scheduled push notifications that are due';

    /**
     * The notification service instance.
     *
     * @var \Funlifew\PushNotify\Services\NotificationService
     */
    protected $notificationService;

    /**
     * Create a new command instance.
     *
     * @param  \Funlifew\PushNotify\Services\NotificationService  $notificationService
     * @return void
     */
    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dueNotifications = ScheduledNotification::due()->get();
        
        if ($dueNotifications->isEmpty()) {
            $this->info('No scheduled notifications due for sending.');
            return 0;
        }
        
        $this->info('Found ' . $dueNotifications->count() . ' notifications to send.');
        
        $sent = 0;
        $failed = 0;
        
        foreach ($dueNotifications as $notification) {
            try {
                $notification->markAsProcessing();
                
                $title = $notification->getNotificationTitle();
                $body = $notification->getNotificationBody();
                $url = $notification->getNotificationUrl();
                $iconPath = $notification->getNotificationIconPath();
                
                // Handle different recipient types
                if ($notification->subscription_id) {
                    // Send to single subscription
                    $subscription = $notification->subscription;
                    $result = $this->notificationService->sendToSubscription(
                        $subscription,
                        $title,
                        $body,
                        $url,
                        $iconPath
                    );
                    
                    if ($result) {
                        $notification->markAsSent();
                        $sent++;
                    } else {
                        $notification->markAsFailed('Failed to send to subscription');
                        $failed++;
                    }
                } elseif ($notification->topic_id) {
                    // Send to topic
                    $topic = $notification->topic;
                    $result = $this->notificationService->sendToTopic(
                        $topic,
                        $title,
                        $body,
                        $url,
                        $iconPath
                    );
                    
                    if ($result['success'] > 0) {
                        $notification->markAsSent();
                        $sent++;
                    } else {
                        $notification->markAsFailed('Failed to send to topic: ' . json_encode($result));
                        $failed++;
                    }
                } elseif ($notification->send_to_all) {
                    // Send to all subscriptions
                    $result = $this->notificationService->sendToAll(
                        $title,
                        $body,
                        $url,
                        $iconPath
                    );
                    
                    if ($result['success'] > 0) {
                        $notification->markAsSent();
                        $sent++;
                    } else {
                        $notification->markAsFailed('Failed to send to all: ' . json_encode($result));
                        $failed++;
                    }
                }
                
            } catch (\Exception $e) {
                Log::error('Error sending scheduled notification: ' . $e->getMessage(), [
                    'notification_id' => $notification->id,
                    'exception' => $e,
                ]);
                
                $notification->markAsFailed($e->getMessage());
                $failed++;
            }
        }
        
        $this->info("Processed {$dueNotifications->count()} notifications: {$sent} sent, {$failed} failed.");
        
        return 0;
    }
}