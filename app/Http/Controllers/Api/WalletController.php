<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Models\TransactionHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    public function wallet()
    {
        $userId = Auth::id();
        $wallet = Wallet::where('user_id', $userId)->first();

        if (!$wallet) {
            return response()->json([
                'responseCode' => '200',
                'responseMessage' => 'Wallet not found',
                'responseType' => 'success',
                'responseData' => [
                    'amount' => 0
                ],
            ], 200);
        }

        // Assuming you want to show the last withdrawal amount as part of the response
        $lastWithdrawal = Withdrawal::where('user_id', $userId)->latest()->first();

        $response = [
            'responseCode' => '200',
            'responseMessage' => 'User Wallet Get Successful',
            'responseType' => 'success',
            'responseData' => [
                'amount' => $lastWithdrawal ? $lastWithdrawal->coins : 0, // Last withdrawal amount or 0 if none
                'total_coins' => $wallet->total_coins
            ],
        ];

        return response()->json($response);
    }

public function withdrawal(Request $request)
{
    $userId = Auth::id();

    $validator = Validator::make($request->all(), [
        'amount' => 'required|numeric|min:0',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'responseCode' => '400',
            'responseMessage' => $validator->errors()->first(),
            'responseType' => 'fail'
        ], 400);
    }

    $amount = $request->amount;
    Log::info('Requested Withdrawal Amount:', ['amount' => $amount]);  // Debugging

    $wallet = Wallet::where('user_id', $userId)->first();

    if (!$wallet) {
        return response()->json([
            'responseCode' => '404',
            'responseMessage' => 'Wallet not found',
            'responseType' => 'fail',
            'responseData' => ['amount' => 0]
        ], 404);
    }

    // Ensure minimum balance check is correct
    if ($wallet->total_coins < 100) {
        return response()->json([
            'responseCode' => '400',
            'responseMessage' => 'Your Wallet Balance is Less than 100',
            'responseType' => 'fail',
            'responseData' => ['amount' => 0]
        ], 400);
    }

    if ($wallet->total_coins < $amount) {
        return response()->json([
            'responseCode' => '400',
            'responseMessage' => 'Insufficient Balance',
            'responseType' => 'fail',
            'responseData' => ['amount' => 0]
        ], 400);
    }

    if ($wallet->status == 0) {
        return response()->json([
            'responseCode' => '400',
            'responseMessage' => 'Deactivated Wallet',
            'responseType' => 'fail',
            'responseData' => ['amount' => 0]
        ], 400);
    }

    DB::beginTransaction();

    try {
        $newCoinValue = $wallet->total_coins - $amount;
        $wallet->total_coins = $newCoinValue;
        $wallet->save();

        $data = [
            'user_id' => $userId,
            'coins' => $amount,
        ];

        $withdrawalRequest = Withdrawal::create($data);
        DB::commit();

        return response()->json([
            'responseCode' => '200',
            'responseMessage' => 'Withdrawal request successful',
            'responseType' => 'success',
            'responseData' => ['amount' => $amount]
        ], 200);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'responseCode' => '500',
            'responseMessage' => 'An error occurred: ' . $e->getMessage(),
            'responseType' => 'fail'
        ], 500);
    }
}


    public function history(Request $request)
    {
        $userId = Auth::id();

        $transactions = TransactionHistory::where('user_id', $userId)->get();

        return response()->json([
            'responseCode' => '200',
            'responseMessage' => 'User Wallet Get Successful',
            'responseType' => 'success',
            'responseData' => $transactions,
        ], 200);
    }

    public function addAmount(Request $request)
    {
        $userId = Auth::id();

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'responseCode' => '400',
                'responseMessage' => $validator->errors()->first(),
                'responseType' => 'fail'
            ], 200);
        }

        $amount = $request->amount;
        $wallet = Wallet::where('user_id', $userId)->first();

        if (!$wallet) {
            return response()->json([
                'responseCode' => '200',
                'responseMessage' => 'Wallet not found',
                'responseType' => 'success',
                'responseData' => [
                    'amount' => 0
                ]
            ], 200);
        }

        if ($wallet->status == 0) {
            return response()->json([
                'responseCode' => '200',
                'responseMessage' => 'Deactivated Wallet',
                'responseType' => 'success',
                'responseData' => [
                    'amount' => 0
                ]
            ], 200);
        }

        DB::beginTransaction();

        try {
            $wallet->total_coins += $amount;
            $wallet->save();

            $transaction = TransactionHistory::create([
                'user_id' => $userId,
                'type' => 1,
                'transaction_id' => uniqid(),
                'amount' => $amount,
                'used_for' => 'Wallet Top-up',
            ]);

            DB::commit();

            return response()->json([
                'responseCode' => '200',
                'responseMessage' => 'Amount added successfully',
                'responseType' => 'success',
                'responseData' => [
                    'amount' => $amount,
                    'total_coins' => $wallet->total_coins
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'responseCode' => '500',
                'responseMessage' => 'An error occurred: ' . $e->getMessage(),
                'responseType' => 'fail'
            ], 200);
        }
    }

    // Add the missing buyHistory method
    public function buyHistory()
    {
        $userId = Auth::id();
        Log::info('User ID for buyHistory:', ['id' => $userId]);

        $transactions = TransactionHistory::where('user_id', $userId)
            ->where('type', 1) // Assuming type 1 is for buy history
            ->get();

        Log::info('Buy History Transactions Retrieved:', ['transactions' => $transactions]);

        return response()->json([
            'responseCode' => '200',
            'responseMessage' => 'Buy History Retrieved Successfully',
            'responseType' => 'success',
            'responseData' => $transactions,
        ], 200);
    }

    public function withdrawalHistory()
    {
        $userId = Auth::id();
        Log::info('User ID for withdrawalHistory:', ['id' => $userId]);

        $transactions = TransactionHistory::where('user_id', $userId)
            ->where('type', 0) // Assuming type 0 is for withdrawal history
            ->get();

        Log::info('Withdrawal History Transactions Retrieved:', ['transactions' => $transactions]);

        return response()->json([
            'responseCode' => '200',
            'responseMessage' => 'Withdrawal History Retrieved Successfully',
            'responseType' => 'success',
            'responseData' => $transactions,
        ], 200);
    }
}
