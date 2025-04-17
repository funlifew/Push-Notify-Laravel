<?php

namespace Funlifew\PushNotify\Models;

use Illuminate\Database\Eloquent\Concerns\HasRelationships;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Message extends Model{
    use HasFactory;
    use HasRelationships;

    public function notifications(){
        return $this->hasMany(Notification::class);
    }
}