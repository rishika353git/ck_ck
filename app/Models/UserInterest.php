<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserInterest extends Model
{
    protected $table = 'user_interest';

    protected $fillable = ['user_id', 'interest_ids'];

    // Cast interest_ids to an array
    protected $casts = [
        'interest_ids' => 'array',
    ];

    // No need for relationship methods as we store the interests as a JSON array
}
