<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Services extends Model
{
    use HasFactory;
    protected $table = 'services';
    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'title',
        'need_help',
        'location',
        'hashtags',
        'post_type',
        'description',

    ];
     // polymorph function to get hashtags
     public function hashtags()
     {
         return $this->morphToMany(Hashtag::class, 'postable', 'hashtag_post');
     }
}
