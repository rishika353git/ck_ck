<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WalletHandle;
use Illuminate\Support\Facades\Auth;

class HandleWalletController extends Controller
{
     /**
     * Get the authenticated user's wallet data.
     */
   public function getWallet()
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

    $wallet = WalletHandle::where('user_id', $user->id)->first();

    return response()->json([
        'responseCode' => '200',
        'responseMessage' => 'User Wallet Get Successful',
        'responseType' => 'success',
        'responseData' => [
            'amount' => $wallet ? number_format((float)$wallet->total_coins, 2, '.', '') : '0.00',
        ]
    ], 200);
}


    /**
     * Post wallet amount.
     */
public function postWallet(Request $request)
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

    $request->validate([
        'amount' => 'required|numeric|min:0',
    ]);

    $wallet = WalletHandle::firstOrCreate(
        ['user_id' => $user->id],
        ['total_coins' => 0]
    );

    $wallet->total_coins += $request->amount;
    $wallet->status = 0; // Set status to 0
    $wallet->save();

    return response()->json([
        'responseCode' => '200',
        'responseMessage' => 'Amount added successfully',
        'responseType' => 'success',
        'responseData' => [
            'amount' => $wallet->total_coins,
            'status' => $wallet->status, // Return the status
            'created_at' => $wallet->created_at->toDateTimeString(), // Return the created_at timestamp
        ]
    ], 200);
}

public function withdrawWallet(Request $request)
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

    $request->validate([
        'amount' => 'required|numeric|min:0',
    ]);

    $wallet = WalletHandle::where('user_id', $user->id)->first();

    if (!$wallet || $wallet->total_coins < $request->amount) {
        return response()->json([
            'responseCode' => '400',
            'responseMessage' => 'Insufficient funds',
            'responseType' => 'error',
            'responseData' => '',
        ], 400);
    }

    $wallet->total_coins -= $request->amount;
    $wallet->status = 1; // Set status to 1
    $wallet->save();

    return response()->json([
        'responseCode' => '200',
        'responseMessage' => 'Withdrawal successful',
        'responseType' => 'success',
        'responseData' => [
            'amount' => $wallet->total_coins,
            'status' => $wallet->status, // Return the status
            'created_at' => $wallet->created_at->toDateTimeString(), // Return the created_at timestamp
        ]
    ], 200);
}


}


