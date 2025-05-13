<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    use HasFactory;
    protected $table ='certificates';
    protected $fillable = [
        'title',
        'path',
        'hashtags',
        'post_type',
        'user_id', 
    ];

    public function hashtags()
    {
        return $this->morphToMany(Hashtag::class, 'postable', 'hashtag_post');
    }
}


