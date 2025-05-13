<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WelcomeTeamTemp extends Model
{
    use HasFactory;

    protected $table = 'welcome_team_temp';

    protected $fillable = [
        'user_id',
      
        'image',
        'welcome_card_id',
        'description',
        'hashtags',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function welcomeCardType()
    {
        return $this->belongsTo(WelcomeCardType::class);
    }
}
