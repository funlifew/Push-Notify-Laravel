<?php

namespace Funlifew\PushNotify\Models;
use Illuminate\Database\Eloquent\Concerns\HasRelationships;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Topic extends Model{
    use HasFactory;
    use HasRelationships;

    public function subscriptions(){
        return $this->belongsToMany(Subscription::class);
    }
}