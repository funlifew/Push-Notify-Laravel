<?php

namespace Funlifew\PushNotify\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ScheduledNotification extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'subscription_id',
        'topic_id',
        'send_to_all',
        'message_id',
        'title',
        'body',
        'url',
        'icon_path',
        'scheduled_at',
        'sent_at',
        'status',
        'error',
        'attempts',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'send_to_all' => 'boolean',
        'attempts' => 'integer',
    ];

    /**
     * Get the subscription this notification is for.
     */
    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Get the topic this notification is for.
     */
    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * Get the message template for this notification.
     */
    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * Get the user who created this scheduled notification.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope a query to only include pending notifications that are due.
     */
    public function scopeDue(Builder $query)
    {
        return $query->where('status', 'pending')
            ->where('scheduled_at', '<=', now())
            ->where('attempts', '<', 3);
    }

    /**
     * Mark this notification as processing.
     */
    public function markAsProcessing()
    {
        $this->status = 'processing';
        $this->attempts = $this->attempts + 1;
        $this->save();
        
        return $this;
    }

    /**
     * Mark this notification as sent.
     */
    public function markAsSent()
    {
        $this->status = 'sent';
        $this->sent_at = now();
        $this->save();
        
        return $this;
    }

    /**
     * Mark this notification as failed.
     */
    public function markAsFailed($error = null)
    {
        $this->status = 'failed';
        $this->error = $error;
        $this->save();
        
        return $this;
    }

    /**
     * Get notification title (from message template or direct value).
     */
    public function getNotificationTitle()
    {
        return $this->message ? $this->message->title : $this->title;
    }

    /**
     * Get notification body (from message template or direct value).
     */
    public function getNotificationBody()
    {
        return $this->message ? $this->message->body : $this->body;
    }

    /**
     * Get notification URL (from message template or direct value).
     */
    public function getNotificationUrl()
    {
        return $this->message && $this->message->url ? $this->message->url : $this->url;
    }

    /**
     * Get notification icon path (from message template or direct value).
     */
    public function getNotificationIconPath()
    {
        return $this->message && $this->message->icon_path ? $this->message->icon_path : $this->icon_path;
    }
}