<?php

namespace Funlifew\PushNotify\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed send(string $title, string $body, array $options = [])
 * @method static mixed sendToSubscription(\Funlifew\PushNotify\Models\Subscription $subscription, string $title, string $body, string $url = null, string $iconPath = null)
 * @method static mixed sendToTopic(\Funlifew\PushNotify\Models\Topic $topic, string $title, string $body, string $url = null, string $iconPath = null)
 * @method static mixed sendToAll(string $title, string $body, string $url = null, string $iconPath = null)
 * @method static mixed sendWithTemplate(\Funlifew\PushNotify\Models\Message $message, \Funlifew\PushNotify\Models\Subscription $subscription = null, \Funlifew\PushNotify\Models\Topic $topic = null, bool $sendToAll = false)
 * @method static \Funlifew\PushNotify\Models\ScheduledNotification scheduleNotification(array $data)
 * @method static mixed schedule(string $title, string $body, $scheduledAt, array $options = [])
 * @method static int getSubscriptionsCount(array $options = [])
 * @method static bool isUserSubscribed(?int $userId = null)
 * 
 * @see \Funlifew\PushNotify\Services\NotificationService
 */
class PushNotify extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'push-notify';
    }
}