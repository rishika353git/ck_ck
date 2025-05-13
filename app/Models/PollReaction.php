<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PollReaction extends Model
{
    use HasFactory;
    protected $table = 'poll_reactions';
    protected $primaryKey = 'id';
    protected $fillable = [
     'poll_id',
     'user_id',
     'status'
    ];
}
