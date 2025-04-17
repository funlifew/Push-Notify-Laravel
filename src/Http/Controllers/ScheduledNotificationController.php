<?php

namespace Funlifew\PushNotify\Http\Controllers;

use App\Http\Controllers\Controller;
use Funlifew\PushNotify\Models\Message;
use Funlifew\PushNotify\Models\ScheduledNotification;
use Funlifew\PushNotify\Models\Subscription;
use Funlifew\PushNotify\Models\Topic;
use Funlifew\PushNotify\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ScheduledNotificationController extends Controller
{
    /**
     * The notification service instance.
     *
     * @var \Funlifew\PushNotify\Services\NotificationService
     */
    protected $notificationService;

    /**
     * Create a new controller instance.
     *
     * @param  \Funlifew\PushNotify\Services\NotificationService  $notificationService
     * @return void
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display a listing of scheduled notifications.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Get notifications by status
        $pending = \Funlifew\PushNotify\Models\ScheduledNotification::where('status', 'pending')
            ->orderBy('scheduled_at')
            ->get();
            
        $sent = \Funlifew\PushNotify\Models\ScheduledNotification::where('status', 'sent')
            ->orderBy('sent_at', 'desc')
            ->get();
            
        $failed = \Funlifew\PushNotify\Models\ScheduledNotification::where('status', 'failed')
            ->orderBy('scheduled_at', 'desc')
            ->get();
            
        return view('push-notify::scheduled.index', compact('pending', 'sent', 'failed'))->withErrors([]);
    }

    /**
     * Show the form for creating a new scheduled notification.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $messages = Message::all();
        $topics = Topic::all();
        $subscriptions = Subscription::all();
        
        return view('push-notify::scheduled.create', compact('messages', 'topics', 'subscriptions'));
    }

    /**
     * Store a newly created scheduled notification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recipient_type' => ['required', 'in:subscription,topic,all'],
            'subscription_id' => ['required_if:recipient_type,subscription', 'nullable', 'exists:subscriptions,id'],
            'topic_id' => ['required_if:recipient_type,topic', 'nullable', 'exists:topics,id'],
            'content_type' => ['required', 'in:custom,template'],
            'message_id' => ['required_if:content_type,template', 'nullable', 'exists:messages,id'],
            'title' => ['required_if:content_type,custom', 'nullable', 'string', 'max:255'],
            'body' => ['required_if:content_type,custom', 'nullable', 'string'],
            'url' => ['nullable', 'url'],
            'icon' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
            'scheduled_at' => ['required', 'date', 'after_or_equal:now'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Process icon if provided
        $iconPath = null;
        if ($request->hasFile('icon')) {
            $iconPath = $this->processIcon($request->file('icon'));
        }

        // Create scheduled notification data
        $scheduledData = [
            'scheduled_at' => Carbon::parse($request->scheduled_at),
            'status' => 'pending',
            'created_by' => Auth::id(),
        ];
        
        // Set recipient based on type
        switch ($request->recipient_type) {
            case 'subscription':
                $scheduledData['subscription_id'] = $request->subscription_id;
                break;
            case 'topic':
                $scheduledData['topic_id'] = $request->topic_id;
                break;
            case 'all':
                $scheduledData['send_to_all'] = true;
                break;
        }
        
        // Set content based on type
        if ($request->content_type === 'template') {
            $scheduledData['message_id'] = $request->message_id;
        } else {
            $scheduledData['title'] = $request->title;
            $scheduledData['body'] = $request->body;
            $scheduledData['url'] = $request->url;
            $scheduledData['icon_path'] = $iconPath;
        }
        
        // Create the scheduled notification
        $this->notificationService->scheduleNotification($scheduledData);
        
        return redirect()->route('notify.scheduled.index')
            ->with('success', 'Notification scheduled successfully.');
    }

    /**
     * Show the scheduled notification details.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $notification = ScheduledNotification::findOrFail($id);
        
        return view('push-notify::scheduled.show', compact('notification'));
    }

    /**
     * Show the form for editing a scheduled notification.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $notification = ScheduledNotification::findOrFail($id);
        
        // Only allow editing pending notifications
        if ($notification->status !== 'pending') {
            return redirect()->route('notify.scheduled.index')
                ->with('error', 'Only pending notifications can be edited.');
        }
        
        $messages = Message::all();
        $topics = Topic::all();
        $subscriptions = Subscription::all();
        
        return view('push-notify::scheduled.edit', compact('notification', 'messages', 'topics', 'subscriptions'));
    }

    /**
     * Update the scheduled notification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $notification = ScheduledNotification::findOrFail($id);
        
        // Only allow updating pending notifications
        if ($notification->status !== 'pending') {
            return redirect()->route('notify.scheduled.index')
                ->with('error', 'Only pending notifications can be updated.');
        }
        
        $validator = Validator::make($request->all(), [
            'recipient_type' => ['required', 'in:subscription,topic,all'],
            'subscription_id' => ['required_if:recipient_type,subscription', 'nullable', 'exists:subscriptions,id'],
            'topic_id' => ['required_if:recipient_type,topic', 'nullable', 'exists:topics,id'],
            'content_type' => ['required', 'in:custom,template'],
            'message_id' => ['required_if:content_type,template', 'nullable', 'exists:messages,id'],
            'title' => ['required_if:content_type,custom', 'nullable', 'string', 'max:255'],
            'body' => ['required_if:content_type,custom', 'nullable', 'string'],
            'url' => ['nullable', 'url'],
            'icon' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
            'scheduled_at' => ['required', 'date', 'after_or_equal:now'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Process icon if provided
        $iconPath = $notification->icon_path;
        if ($request->hasFile('icon')) {
            $iconPath = $this->processIcon($request->file('icon'));
        }

        // Reset all recipient fields
        $notification->subscription_id = null;
        $notification->topic_id = null;
        $notification->send_to_all = false;
        
        // Set recipient based on type
        switch ($request->recipient_type) {
            case 'subscription':
                $notification->subscription_id = $request->subscription_id;
                break;
            case 'topic':
                $notification->topic_id = $request->topic_id;
                break;
            case 'all':
                $notification->send_to_all = true;
                break;
        }
        
        // Set content based on type
        if ($request->content_type === 'template') {
            $notification->message_id = $request->message_id;
            $notification->title = null;
            $notification->body = null;
            $notification->url = null;
            $notification->icon_path = null;
        } else {
            $notification->message_id = null;
            $notification->title = $request->title;
            $notification->body = $request->body;
            $notification->url = $request->url;
            $notification->icon_path = $iconPath;
        }
        
        $notification->scheduled_at = Carbon::parse($request->scheduled_at);
        $notification->save();
        
        return redirect()->route('notify.scheduled.index')
            ->with('success', 'Scheduled notification updated successfully.');
    }

    /**
     * Send a scheduled notification immediately.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendNow($id)
    {
        $notification = ScheduledNotification::findOrFail($id);
        
        // Only allow sending pending notifications
        if ($notification->status !== 'pending') {
            return redirect()->route('notify.scheduled.index')
                ->with('error', 'Only pending notifications can be sent.');
        }
        
        try {
            $title = $notification->getNotificationTitle();
            $body = $notification->getNotificationBody();
            $url = $notification->getNotificationUrl();
            $iconPath = $notification->getNotificationIconPath();
            
            $result = false;
            
            if ($notification->subscription_id) {
                // Send to specific subscription
                $result = $this->notificationService->sendToSubscription(
                    $notification->subscription,
                    $title,
                    $body,
                    $url,
                    $iconPath
                );
            } elseif ($notification->topic_id) {
                // Send to topic
                $result = $this->notificationService->sendToTopic(
                    $notification->topic,
                    $title,
                    $body,
                    $url,
                    $iconPath
                );
            } elseif ($notification->send_to_all) {
                // Send to all
                $result = $this->notificationService->sendToAll(
                    $title,
                    $body,
                    $url,
                    $iconPath
                );
            }
            
            if ($result) {
                $notification->markAsSent();
                return redirect()->route('notify.scheduled.index')
                    ->with('success', 'Notification sent successfully.');
            }
            
            $notification->markAsFailed('Failed to send notification');
            return redirect()->route('notify.scheduled.index')
                ->with('error', 'Failed to send notification.');
            
        } catch (\Exception $e) {
            $notification->markAsFailed($e->getMessage());
            return redirect()->route('notify.scheduled.index')
                ->with('error', 'Error sending notification: ' . $e->getMessage());
        }
    }

    /**
     * Cancel a scheduled notification.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cancel($id)
    {
        $notification = ScheduledNotification::findOrFail($id);
        
        // Only allow canceling pending notifications
        if ($notification->status !== 'pending') {
            return redirect()->route('notify.scheduled.index')
                ->with('error', 'Only pending notifications can be canceled.');
        }
        
        $notification->delete();
        
        return redirect()->route('notify.scheduled.index')
            ->with('success', 'Scheduled notification canceled successfully.');
    }

    /**
     * Process and store icon image.
     *
     * @param  \Illuminate\Http\UploadedFile  $icon
     * @return string|null
     */
    protected function processIcon($icon)
    {
        if (!$icon) {
            return null;
        }
        
        $disk = config('push-notify.storage.disk', 'public');
        $path = config('push-notify.storage.icons_path', 'icons');
        $quality = config('push-notify.storage.image_quality', 75);
        $width = config('push-notify.storage.icon_width', 64);
        $height = config('push-notify.storage.icon_height', 64);
        
        // Process image with Intervention Image if available
        if (class_exists('Intervention\Image\Laravel\Facades\Image')) {
            $image = \Intervention\Image\Laravel\Facades\Image::read($icon)
                ->resize($width, $height)
                ->toJpeg($quality);
            
            $filename = $path . '/' . uniqid() . '.' . $icon->getClientOriginalExtension();
            \Illuminate\Support\Facades\Storage::disk($disk)->put($filename, $image);
            
            return $filename;
        }
        
        // Fallback to simple storage
        $filename = $icon->store($path, $disk);
        
        return $filename;
    }
}