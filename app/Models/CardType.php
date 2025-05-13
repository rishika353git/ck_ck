<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CardType extends Model
{
    protected $table = 'card_types';
    use HasFactory;
    protected $fillable = [
        'card_title','card_description','card_image'
    ];
}
