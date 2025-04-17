<?php

namespace Funlifew\PushNotify\Http\Controllers;

use Funlifew\PushNotify\Models\Message;
use Funlifew\PushNotify\Models\Subscription;
use Funlifew\PushNotify\Models\Topic;
use Funlifew\PushNotify\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;

class TopicController extends Controller
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
     * Display a listing of topics.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $topics = Topic::withCount('subscriptions')->orderBy('name')->paginate(15);
        
        return view('push-notify::topics.index', compact('topics'));
    }

    /**
     * Show the form for creating a new topic.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $subscriptions = Subscription::with('user')->get();
        
        return view('push-notify::topics.create', compact('subscriptions'));
    }

    /**
     * Store a newly created topic in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:topics'],
            'subscriptions' => ['nullable', 'array'],
            'subscriptions.*' => ['exists:subscriptions,id'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $topic = new Topic();
        $topic->name = $request->input('name');
        $topic->slug = Str::slug($request->input('slug'));
        $topic->save();
        
        if ($request->has('subscriptions')) {
            $topic->subscriptions()->attach($request->input('subscriptions'));
        }
        
        return redirect()->route('notify.topics.index')
            ->with('success', 'Topic created successfully.');
    }

    /**
     * Show the form for editing the specified topic.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $topic = Topic::findOrFail($id);
        $subscriptions = Subscription::with('user')->get();
        $selectedSubscriptions = $topic->subscriptions->pluck('id')->toArray();
        
        return view('push-notify::topics.edit', compact('topic', 'subscriptions', 'selectedSubscriptions'));
    }

    /**
     * Update the specified topic in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $topic = Topic::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:topics,slug,'.$topic->id],
            'subscriptions' => ['nullable', 'array'],
            'subscriptions.*' => ['exists:subscriptions,id'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $topic->name = $request->input('name');
        $topic->slug = Str::slug($request->input('slug'));
        $topic->save();
        
        // Sync subscriptions
        $subscriptions = $request->input('subscriptions', []);
        $topic->subscriptions()->sync($subscriptions);
        
        return redirect()->route('notify.topics.index')
            ->with('success', 'Topic updated successfully.');
    }

    /**
     * Remove the specified topic from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $topic = Topic::findOrFail($id);
        
        // Detach all subscriptions
        $topic->subscriptions()->detach();
        
        // Delete the topic
        $topic->delete();
        
        return redirect()->route('notify.topics.index')
            ->with('success', 'Topic deleted successfully.');
    }

    /**
     * Show the form for sending notification to a topic.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function showSendForm($id)
    {
        $topic = Topic::withCount('subscriptions')->findOrFail($id);
        $messages = Message::all();
        
        return view('push-notify::topics.send', compact('topic', 'messages'));
    }

    /**
     * Send notification to a topic.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendNotification(Request $request, $id)
    {
        $topic = Topic::withCount('subscriptions')->findOrFail($id);
        
        if ($topic->subscriptions_count === 0) {
            return redirect()->back()->with('error', 'This topic has no subscribers.');
        }
        
        $validator = Validator::make($request->all(), [
            'content_type' => ['required', 'in:custom,template'],
            'title' => ['required_if:content_type,custom', 'nullable', 'string', 'max:255'],
            'body' => ['required_if:content_type,custom', 'nullable', 'string'],
            'url' => ['nullable', 'url'],
            'icon' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
            'message_id' => ['required_if:content_type,template', 'nullable', 'exists:messages,id'],
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
        if ($request->has('schedule') && $request->filled('scheduled_at')) {
            $scheduledData = [
                'topic_id' => $topic->id,
                'scheduled_at' => Carbon::parse($request->scheduled_at),
                'status' => 'pending',
                'created_by' => Auth::id(),
            ];
            
            if ($request->input('content_type') === 'template' && $request->filled('message_id')) {
                $scheduledData['message_id'] = $request->input('message_id');
            } else {
                $scheduledData['title'] = $request->input('title');
                $scheduledData['body'] = $request->input('body');
                $scheduledData['url'] = $request->input('url');
                $scheduledData['icon_path'] = $iconPath;
            }
            
            $this->notificationService->scheduleNotification($scheduledData);
            
            return redirect()->route('notify.topics.index')
                ->with('success', 'Notification scheduled for topic successfully.');
        }
        
        // Send immediately
        if ($request->input('content_type') === 'template' && $request->filled('message_id')) {
            $message = Message::find($request->input('message_id'));
            $result = $this->notificationService->sendWithTemplate($message, null, $topic);
        } else {
            $result = $this->notificationService->sendToTopic(
                $topic,
                $request->input('title'),
                $request->input('body'),
                $request->input('url'),
                $iconPath
            );
        }
        
        if (isset($result['success']) && $result['success'] > 0) {
            return redirect()->route('notify.topics.index')
                ->with('success', "Notification sent to {$result['success']} subscribers in topic \"{$topic->name}\".");
        }
        
        return redirect()->route('notify.topics.index')
            ->with('error', 'Failed to send notification to topic.');
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