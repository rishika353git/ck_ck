<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletHandle extends Model
{
    use HasFactory;
    protected $table = 'wallet_handles';
    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'total_coins',
        'status',
    ];

}
