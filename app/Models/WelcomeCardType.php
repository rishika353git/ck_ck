<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WelcomeCardType extends Model
{
    use HasFactory;
    protected $table='welcome_cardtypes';
    protected $fillable = [
        'welcome_card_title',
        'welcome_card_description',
        'welcome_card_image',
    ];
}

