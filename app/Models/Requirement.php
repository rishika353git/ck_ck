<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Requirement extends Model
{
    use HasFactory;
    protected $table = 'requirement_listing';
    protected $primaryKey = 'id';

    protected $fillable = [
       
        'job_id',
        'user_id',
        'title',
        'description',
        'location',
    ];
}
