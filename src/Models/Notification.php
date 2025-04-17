<?php
namespace Funlifew\PushNotify\Models;

use Illuminate\Database\Eloquent\Concerns\HasRelationships;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Notification extends Model {
    use HasFactory;
    use HasRelationships;

    protected $fillable = [
        "title",
        "body",
        "url",
        "icon",
        "message_id",
        "subscription_id",
        "status",
    ];
    public function subscription(){
        return $this->belongsTo(Subscription::class);
    }

    public function message(){
        return $this->belongsTo(Message::class);
    }
}