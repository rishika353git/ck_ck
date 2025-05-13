<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\TransactionHandle;

class TransactionHandlerController extends Controller
{
    public function getTransactions()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'responseCode' => '401',
                'responseMessage' => 'Unauthorized',
                'responseType' => 'error',
                'responseData' => '',
            ], 401);
        }

        $transactions = TransactionHandle::where('user_id', $user->id)->get();

        return response()->json([
            'responseCode' => '200',
            'responseMessage' => 'Transaction history retrieved successfully',
            'responseType' => 'success',
            'responseData' => $transactions
        ], 200);
    }
}
