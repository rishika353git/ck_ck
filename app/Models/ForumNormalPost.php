<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ForumNormalPost extends Model
{
    use HasFactory;
    protected $table = 'forum_normal_post';
    protected $primaryKey = 'id';
    protected $fillable = [
        'user_id',
        'description',
        'files',
        'hashtags',
        'post_type',
        'upvote',
        'downvote',
        'share',
        'repost',
    ];
   // polymorph function to get hashtags
    public function hashtags()
    {
        return $this->morphToMany(Hashtag::class, 'postable', 'hashtag_post');
    }
}
