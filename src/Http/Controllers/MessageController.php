<?php

namespace Funlifew\PushNotify\Http\Controllers;

use App\Http\Controllers\Controller;
use Funlifew\PushNotify\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
    /**
     * Display a listing of message templates.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $messages = Message::paginate(15);
        
        return view('push-notify::messages.index', compact('messages'));
    }

    /**
     * Show the form for creating a new message template.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        // Share empty errors bag with the view to avoid "undefined variable" errors
        return view('push-notify::messages.create')->withErrors([]);
    }

    /**
     * Store a newly created message template.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'url' => ['nullable', 'url'],
            'icon' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $message = new Message();
        $message->title = $request->title;
        $message->body = $request->body;
        $message->url = $request->url;
        
        if ($request->hasFile('icon')) {
            $message->icon_path = $this->processIcon($request->file('icon'));
        }
        
        $message->save();
        
        return redirect()->route('notify.messages.index')
            ->with('success', 'Message template created successfully.');
    }

    /**
     * Show the form for editing a message template.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $message = Message::findOrFail($id);
        
        return view('push-notify::messages.edit', compact('message'));
    }

    /**
     * Update a message template.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $message = Message::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'url' => ['nullable', 'url'],
            'icon' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $message->title = $request->title;
        $message->body = $request->body;
        $message->url = $request->url;
        
        if ($request->hasFile('icon')) {
            // Delete old icon if exists
            if ($message->icon_path && Storage::disk(config('push-notify.storage.disk', 'public'))->exists($message->icon_path)) {
                Storage::disk(config('push-notify.storage.disk', 'public'))->delete($message->icon_path);
            }
            
            // Save new icon
            $message->icon_path = $this->processIcon($request->file('icon'));
        }
        
        $message->save();
        
        return redirect()->route('notify.messages.index')
            ->with('success', 'Message template updated successfully.');
    }

    /**
     * Remove a message template.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $message = Message::findOrFail($id);
        
        // Delete icon if exists
        if ($message->icon_path && Storage::disk(config('push-notify.storage.disk', 'public'))->exists($message->icon_path)) {
            Storage::disk(config('push-notify.storage.disk', 'public'))->delete($message->icon_path);
        }
        
        $message->delete();
        
        return redirect()->route('notify.messages.index')
            ->with('success', 'Message template deleted successfully.');
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