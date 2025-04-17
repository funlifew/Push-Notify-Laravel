<?php
namespace Funlifew\PushNotify\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Funlifew\PushNotify\Models\Message;
use Funlifew\PushNotify\Models\Notification;
use Funlifew\PushNotify\Models\Subscription;
use Funlifew\PushNotify\Models\Topic;
use Str;

class TopicController extends Controller{
    function create(){
        $subscriptions = Subscription::all();
        return view("notify::topic.create", compact("subscriptions"));
    }

    function store(Request $request){
        $request->validate([
            "name" => ["required", "string"],
            "slug" => ["required", "string"],
            "subscriptions" => ["nullable", "array"],
            "subscriptions.*" => ["exists:subscriptions,id"],
        ]);
        $topic = new Topic();
        $topic->name = $request->name;
        $topic->slug = Str::slug($request->slug);
        $topic->save();

        if($request->has("subscriptions")){
            $topic->subscriptions()->sync($request->subscriptions);
        }
        return redirect()->route("notify");
    }

    function edit(string $id){
        $topic = Topic::findOrFail($id);
        $subscriptions = Subscription::all();
        $selectedSubscriptions = $topic->subscriptions->pluck('id')->toArray();
        return view('notify::topic.update', compact('topic','selectedSubscriptions', "subscriptions"));
    }

    function update(Request $request, string $id){
        $request->validate([
            "name" => ["required", "string"],
            "slug" => ["required", "string"],
            "subscriptions" => ["nullable", "array"],
            "subscriptions.*" => ["exists:subscriptions,id"],
        ]);

        $topic = Topic::findOrFail($id);
        $topic->name = $request->name;
        $topic->slug = Str::slug($request->slug);
        $topic->save();
        if($request->has("subscriptions")){
            $topic->subscriptions()->sync($request->subscriptions);
        }
        return redirect()->route("notify");
    }

    function destroy(string $id){
        $topic = Topic::findOrFail($id);
        $topic->delete();
        return redirect()->route("notify");
    }

    function sendToTopic(string $id){
        $topic = Topic::findOrFail($id);
        $messages = Message::all();
        return view("notify::topic.send", compact("topic", "messages"));
    }
    
    function sendToTopicStore(Request $request, string $id){
        $topic = Topic::findOrFail($id);
        $request->validate([
            "title" => ["nullable", "string"],
            "body" => ["nullable", "string"],
            "url" => ["nullable", "url"],
            "icon" => ["nullable", "image", "mimes:png,jpeg,jpg", "max:2048"],
            "message" => ["nullable", "exists:messages,id"],
        ]);
        
        $icon=null;
        if($request->hasFile("icon")){
            $icon = compress_images($request->file('icon'));
        }

        $subscription_info_list = $topic->subscriptions->map(function ($subscription){
            return [
                "endpoint" => $subscription->endpoint,
                "keys" => [
                    "auth" => $subscription->auth_key,
                    "p256dh" => $subscription->p256dh_key,
                ],
            ];
        })->toArray();

        $subscriptionsByEndpoint = Subscription::all()->keyBy("endpoint");
        $message = Message::find($request->messages);
        if($message){
            $send_notification = json_decode(sendNotificationGroup($subscription_info_list, $message->title, $message->body, $message->url, $message->icon_path), true);
        } else{
            $send_notification = json_decode(sendNotificationGroup($subscription_info_list, $request->title, $request->body, $request->url, $icon), true);
        }
        $notificationData = [];

        foreach($send_notification["success"] as $success_notify){
            if(isset($subscriptionsByEndpoint[$success_notify])){
                if(!$message){
                    $notification = [
                        "title" => $request->title,
                        "body"=> $request->body,
                        "url"=> $request->url,
                        "subscription_id" => $subscriptionsByEndpoint[$success_notify]->id,
                        "status"=>true
                    ];
                } else{
                    $notification = [
                        "subscription_id" => $subscriptionsByEndpoint[$success_notify]->id,
                        "message_id" => $message->id,
                        "status" => true
                    ];
                }
                $notificationData[] = $notification;
            }
        }

        foreach($send_notification["error"] as $error_notify){
            if(isset($subscriptionsByEndpoint[$error_notify])){
                if(!$message){
                    $notification = [
                        "title" => $request->title,
                        "body"=> $request->body,
                        "url"=> $request->url,
                        "subscription_id" => $subscriptionsByEndpoint[$error_notify]->id,
                        "status"=>true
                    ];
                } else{
                    $notification = [
                        "subscription_id" => $subscriptionsByEndpoint[$success_notify]->id,
                        "message_id" => $message->id,
                        "status" => false
                    ];
                }
                $notificationData[] = $notification;
            }
        }

        if(!empty($notificationData)){
            Notification::insert($notificationData);
        }
        return redirect()->route("notify");
    }
}