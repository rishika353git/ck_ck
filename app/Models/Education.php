<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Education extends Model
{
    use HasFactory;
    protected $table = 'education';
    protected $primaryKey = 'id';
    protected $fillable = [
        'user_id', 
        'title',
        'hashtags',
        'post_type',
        'path',
    ];
    public function hashtags()
    {
        return $this->morphToMany(Hashtag::class, 'postable', 'hashtag_post');
    }
}
