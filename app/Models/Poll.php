<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Poll extends Model
{
    use HasFactory;
    protected $fillable = [
           'user_id',
           'ask_a_question',
           'pollsRespondCount',
            'status', 
            'poll_duration',
            'hashtags',
            'post_type',
            'upvote',
            'downvote'
    ];

    public function choices()
    {
        return $this->hasMany(Choice::class);
    }

    //polymorphic relation to get hashtags associated
public function hashtags()
{
    return $this->morphToMany(Hashtag::class, 'postable', 'hashtag_post');
}
}
