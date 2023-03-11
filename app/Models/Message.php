<?php

namespace App\Models;

use Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class Message extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $casts = [
        'files' => 'json'
    ];

    public function sender()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function getSenderRoleAttribute(){
        return $this->sender->firstRole[0]->name;
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
    public function scopeUnread($query)
    {
        $pivot = $this->users()->getTable();

        $query->whereHas('users', function ($q) use ($pivot) {
            $q->where("{$pivot}.read", 0);
        });
    }
    public function scopeRead($query)
    {
        $pivot = $this->users()->getTable();

        $query->whereHas('users', function ($q) use ($pivot) {
            $q->where("{$pivot}.read", 1);
        });
    }
    public function scopeReceived($query)
    {
        $receivedMessages = auth()->user()->allMessages()->pluck('id');
        $query->whereIn('id',$receivedMessages);
    }
    public function scopeSent($query)
    {
        $query->where('user_id',auth()->id());
    }
    public function hasFiles()
    {
        return $this->files != [] ;
    }
}
