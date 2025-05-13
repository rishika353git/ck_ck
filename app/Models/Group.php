<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;
    protected $primaryKey = 'id';
    protected $table = 'groups';
    protected $fillable = [
       'name',
       'description',
       'logo',
       'joinedMembers',

    ];

    protected $casts = [
        'joinedMembers' => 'array',
        
    ];

    //reln to user model
    public function joinedUsers()
    {
        return $this->belongsToMany(User::class, 'users', 'id', 'id')
            ->whereIn('id', $this->joinedMembers);
    }
}
