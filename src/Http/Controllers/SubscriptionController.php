<?php

namespace Funlifew\PushNotify\Http\Controllers;

use App\Http\Controllers\Controller;
use Funlifew\PushNotify\Models\Message;
use Funlifew\PushNotify\Models\Notification;
use Funlifew\PushNotify\Models\ScheduledNotification;
use Funlifew\PushNotify\Models\Subscription;
use Funlifew\PushNotify\Models\Topic;
use Funlifew\PushNotify\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
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
     * Display the dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function dashboard()
    {
        $subscriptions = Subscription::paginate(10, ['*'], 'subscriptions_page')->withQueryString();
        $messages = Message::paginate(10, ['*'], 'messages_page')->withQueryString();
        $notifications = Notification::paginate(10, ['*'], 'notifications_page')->withQueryString();
        $topics = Topic::paginate(10, ['*'], 'topics_page')->withQueryString();
        $scheduledNotifications = ScheduledNotification::where('status', 'pending')
            ->orderBy('scheduled_at')
            ->paginate(10, ['*'], 'scheduled_page')
            ->withQueryString();

        return view('notify::dashboard', compact(
            'subscriptions', 
            'messages', 
            'notifications', 
            'topics',
            'scheduledNotifications'
        ));
    }

    /**
     * Store a new subscription.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subscription.endpoint' => ['required', 'url', Rule::unique('subscriptions', 'endpoint')],
            'subscription.keys.auth' => ['required', 'string'],
            'subscription.keys.p256dh' => ['required', 'string'],
            'user_id' => ['nullable', 'exists:users,id'],
            'topics' => ['array'],
            'topics.*' => ['string', 'exists:topics,slug'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Detect device and OS
        $device = $request->header('User-Agent');
        $os = $this->detectOperatingSystem($device);
        $ip = $request->ip();

        // Create subscription
        $subscription = Subscription::create([
            'user_id' => $request->user_id ?? Auth::id(),
            'endpoint' => $request->input('subscription.endpoint'),
            'auth_key' => $request->input('subscription.keys.auth'),
            'p256dh_key' => $request->input('subscription.keys.p256dh'),
            'device' => $device,
            'os' => $os,
            'last_used_at' => Carbon::now(),
            'ip_address' => $ip,
        ]);

        // Sync topics if provided
        if ($request->has('topics') && !empty($request->topics)) {
            $topics = Topic::whereIn('slug', $request->topics)->get();
            $subscription->topics()->sync($topics->pluck('id'));
        }

        return response()->json([
            'message' => 'Subscription added successfully.',
            'subscription' => $subscription,
        ], 201);
    }

    /**
     * Show send notification form for a specific subscription.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function showSendForm($id)
    {
        $subscription = Subscription::findOrFail($id);
        $messages = Message::all();
        $topics = Topic::all();
        
        return view('notify::subscriptions.send', compact('subscription', 'messages', 'topics'));
    }

    /**
     * Send notification to a subscription.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendNotification(Request $request, $id)
    {
        $subscription = Subscription::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'title' => ['nullable', 'string', 'required_without:message_id'],
            'body' => ['nullable', 'string', 'required_without:message_id'],
            'url' => ['nullable', 'url'],
            'icon' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
            'message_id' => ['nullable', 'exists:messages,id', 'required_without:title'],
            'scheduled_at' => ['nullable', 'date', 'after_or_equal:now'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Process icon if provided
        $iconPath = null;
        if ($request->hasFile('icon')) {
            $iconPath = $this->processIcon($request->file('icon'));
        }

        // If scheduling is requested
        if ($request->has('scheduled_at') && $request->scheduled_at) {
            $scheduledData = [
                'subscription_id' => $subscription->id,
                'scheduled_at' => Carbon::parse($request->scheduled_at),
                'status' => 'pending',
                'created_by' => Auth::id(),
            ];
            
            if ($request->has('message_id') && $request->message_id) {
                $scheduledData['message_id'] = $request->message_id;
            } else {
                $scheduledData['title'] = $request->title;
                $scheduledData['body'] = $request->body;
                $scheduledData['url'] = $request->url;
                $scheduledData['icon_path'] = $iconPath;
            }
            
            $this->notificationService->scheduleNotification($scheduledData);
            
            return redirect()->route('notify.dashboard')
                ->with('success', 'Notification scheduled successfully.');
        }
        
        // Send immediately
        if ($request->has('message_id') && $request->message_id) {
            $message = Message::find($request->message_id);
            $result = $this->notificationService->sendWithTemplate($message, $subscription);
        } else {
            $result = $this->notificationService->sendToSubscription(
                $subscription,
                $request->title,
                $request->body,
                $request->url,
                $iconPath
            );
        }
        
        if ($result) {
            return redirect()->route('notify.dashboard')
                ->with('success', 'Notification sent successfully.');
        }
        
        return redirect()->route('notify.dashboard')
            ->with('error', 'Failed to send notification.');
    }

    /**
     * Show send to all form.
     *
     * @return \Illuminate\View\View
     */
    public function showSendAllForm()
    {
        $messages = Message::all();
        $topics = Topic::all();
        
        return view('notify::subscriptions.send-all', compact('messages', 'topics'));
    }

    /**
     * Send notification to all subscriptions.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendToAll(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => ['nullable', 'string', 'required_without:message_id'],
            'body' => ['nullable', 'string', 'required_without:message_id'],
            'url' => ['nullable', 'url'],
            'icon' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
            'message_id' => ['nullable', 'exists:messages,id', 'required_without:title'],
            'scheduled_at' => ['nullable', 'date', 'after_or_equal:now'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Process icon if provided
        $iconPath = null;
        if ($request->hasFile('icon')) {
            $iconPath = $this->processIcon($request->file('icon'));
        }

        // If scheduling is requested
        if ($request->has('scheduled_at') && $request->scheduled_at) {
            $scheduledData = [
                'send_to_all' => true,
                'scheduled_at' => Carbon::parse($request->scheduled_at),
                'status' => 'pending',
                'created_by' => Auth::id(),
            ];
            
            if ($request->has('message_id') && $request->message_id) {
                $scheduledData['message_id'] = $request->message_id;
            } else {
                $scheduledData['title'] = $request->title;
                $scheduledData['body'] = $request->body;
                $scheduledData['url'] = $request->url;
                $scheduledData['icon_path'] = $iconPath;
            }
            
            $this->notificationService->scheduleNotification($scheduledData);
            
            return redirect()->route('notify.dashboard')
                ->with('success', 'Notification scheduled for all subscribers.');
        }
        
        // Send immediately
        if ($request->has('message_id') && $request->message_id) {
            $message = Message::find($request->message_id);
            $result = $this->notificationService->sendWithTemplate($message, null, null, true);
        } else {
            $result = $this->notificationService->sendToAll(
                $request->title,
                $request->body,
                $request->url,
                $iconPath
            );
        }
        
        if ($result && isset($result['success']) && $result['success'] > 0) {
            return redirect()->route('notify.dashboard')
                ->with('success', "Notification sent to {$result['success']} subscribers. Failed: {$result['failed']}");
        }
        
        return redirect()->route('notify.dashboard')
            ->with('error', 'Failed to send notification to subscribers.');
    }

    /**
     * Delete a subscription.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $subscription = Subscription::findOrFail($id);
        $subscription->delete();
        
        return redirect()->route('notify.dashboard')
            ->with('success', 'Subscription deleted successfully.');
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

    /**
     * Detect the operating system from the user agent.
     *
     * @param  string  $userAgent
     * @return string
     */
    protected function detectOperatingSystem($userAgent)
    {
        $userAgent = strtolower($userAgent);
        
        if (strpos($userAgent, 'windows') !== false) {
            return 'Windows';
        }
        
        if (strpos($userAgent, 'mac') !== false) {
            return 'MacOS';
        }
        
        if (strpos($userAgent, 'linux') !== false) {
            return 'Linux';
        }
        
        if (strpos($userAgent, 'android') !== false) {
            return 'Android';
        }
        
        if (strpos($userAgent, 'iphone') !== false || strpos($userAgent, 'ipad') !== false) {
            return 'iOS';
        }
        
        return 'Unknown';
    }
}