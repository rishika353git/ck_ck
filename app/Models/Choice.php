<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Choice extends Model
{
    use HasFactory;
    protected $table = 'choices';
    protected $fillable = [
        'poll_id',
        'title',
        'respondCount',
        'respondedUsers',
    ];
    public function poll()
    {
        return $this->belongsTo(Poll::class);
    }
    
    //polymorphic relation to get hashtags associated
public function hashtags()
{
    return $this->morphToMany(Hashtag::class, 'postable', 'hashtag_post');
}
}
