<?php

namespace Funlifew\PushNotify\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasRelationships;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Subscription extends Model{
    use HasFactory;
    use HasRelationships;

    protected $fillable = [
        "user_id",
        "endpoint",
        "auth_key",
        "p256dh_key",
        "device",
        "os",
        "ip_address",
        "last_used_at",
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }
    public function topics(){
        return $this->belongsToMany(Topic::class);
    }

    public function notifications(){
        return $this->hasMany(Notification::class);
    }
}