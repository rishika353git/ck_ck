<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kudo extends Model
{
    use HasFactory;

    protected $table = 'kudos';

    protected $fillable = [
        'user_id',  
        'title',
        'description',
        'file_image',
       // 'kudos_details',
        'hashtags',
        'kudos_card_id',     
        'kudos_title',        
        'kudos_description',  
        'kudos_image',    
        'post_type',    
        'following_ids',
    ];

    protected $casts = [
        'kudos_details' => 'array',
        'following_ids' => 'array',
    ];

    // Relationship with User model
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFollowingNamesAttribute()
    {
        if (is_array($this->following_ids)) {
            return User::whereIn('id', $this->following_ids)->get(['name']);
        }

        return collect();
    }

   // polymorph function to get hashtags
    public function hashtags()
    {
        return $this->morphToMany(Hashtag::class, 'postable', 'hashtag_post');
    }
}




