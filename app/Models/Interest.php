<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Interest extends Model
{
    use HasFactory;
     protected $table = 'interests';
    protected $fillable = [
        'title',
        'icon',
        'color',
    ];
  
public function users()
{
    return $this->belongsToMany(User::class, 'user_interest', 'interest_id', 'user_id');
}

}
