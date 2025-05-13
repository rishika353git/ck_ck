<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TopicnGroup extends Model
{
    use HasFactory;
    protected $table = 'topicn_groups';
    protected $primaryKey = 'id';
    protected $fillable = [
        'title',
        'description',
        'image',
        'membersCount',
        'bgColor',
        'status'
    ];


}
