<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionHandle extends Model

{
    use HasFactory;
    protected $table = 'transaction_handles';
    protected $primaryKey = 'id';
    protected $fillable = [
    'user_id',
    'amount',
    'status',
    ];
}
