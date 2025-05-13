<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Welcome extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'welcomes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',          
        'title',
        'description',
        'file_image',
        // 'card_details',
        'welcomes_card_id',
        'welcomes_title',        
        'welcomes_description',  
        'welcomes_image', 
        'hashtags',
        'post_type',
        'following_ids'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'card_details' => 'array',
        'following_ids'=>'array'
    ];
    
     // Relationship with User model
    public function user()
    {
        return $this->belongsTo(User::class);
}

//polymorphic relation to get hashtags associated
public function hashtags()
{
    return $this->morphToMany(Hashtag::class, 'postable', 'hashtag_post');
}

 public function getFollowingNamesAttribute()
    {
        if (is_array($this->following_ids)) {
            return User::whereIn('id', $this->following_ids)->get(['name']);
        }

        return collect();
    }
}
