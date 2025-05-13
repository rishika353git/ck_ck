<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserInterest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
  
public function login(Request $request)
{
    // Early exit for validation failure to minimize unnecessary processing
    $validator = Validator::make($request->all(), [
        'loginid' => 'required',
        'password' => 'required',
        'fcm_token' => 'required|string',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'responseCode' => '400',
            'responseMessage' => $validator->errors()->first(),
            'responseType' => 'fail',
        ], 200);
    }

    // Retrieve user by login ID using one database query with a union to reduce query count
    $user = User::where('email', $request->loginid)
        ->orWhere('mobile', $request->loginid)
        ->first();

    if ($user && Hash::check($request->password, $user->password)) {
        // Authenticate the user
        Auth::login($user);

        // Save FCM token and generate auth token in one go
        $user->update(['fcm_token' => $request->fcm_token]);

        $token = $user->createToken('auth-token')->plainTextToken;

        // Fetch interests efficiently
        $userInterests = UserInterest::where('user_id', $user->id)->pluck('interest_ids')->first();
        $interestIds = $userInterests ? $userInterests : [];

        $userData = [
            'id' => $user->id,
            'name' => $user->name ?? '',
            'email' => $user->email ?? '',
            'mobile' => $user->mobile ?? '',
            'profile' => $user->profile ?? '',
            'bannerImage' => $user->bannerImage ?? '',
            'fcm_token' => $user->fcm_token,
            'interests' => $interestIds,
        ];

        $verificationStatus = $this->determineVerificationStatus($user);
        $responseMessage = $this->getResponseMessage($verificationStatus);

        // Prepare the response array
        $response = [
            'responseCode' => '200',
            'verificationStatus' => $verificationStatus,
            'responseMessage' => $responseMessage,
            'responseType' => 'success',
            'token' => $verificationStatus == '7' ? $token : '',
            'data' => $userData,
           
        ];

       
        if ($verificationStatus == '5') {
            $response['reason'] = $user->reason;
        }

        return response()->json($response, 200);
    }

    return response()->json([
        'responseCode' => '401',
        'verificationStatus' => '8',
        'responseMessage' => 'Login failed. Incorrect credentials.',
        'responseType' => 'fail',
    ], 200);
}

private function determineVerificationStatus($user)
{
    if ($user->mobile_verified == 0) {
        return '0'; // Mobile Number Not Verified
    } elseif ($user->user_roll == 0) {
        return '1'; // User Role Not Verified
    } elseif (empty($user->card_front)) {
        return '2'; // Front identity Card Empty
    } elseif (empty($user->card_back)) {
        return '3'; // Back identity Card Empty
    } elseif ($user->card_verified == 0) {
        return '4'; // User Pending
    } elseif ($user->card_verified == 2) {
        return '5'; // User Rejected By Admin
    } elseif ($user->card_verified == 3) {
        return '6'; // User Blocked
    }

    return '7'; // Verified (Login Successful)
}
private function getResponseMessage($verificationStatus)
{
    $messages = [
        '0' => 'Mobile Number Not Verified',
        '1' => 'User Role Not Verified',
        '2' => 'Front Identity Card Empty',
        '3' => 'Back Identity Card Empty',
        '4' => 'User Pending',
        '5' => 'User Rejected By Admin',
        '6' => 'You’re Blocked',
        '7' => 'Login Successful',
    ];

    return $messages[$verificationStatus] ?? 'Unknown status';
}

public function logout(Request $request)
{
    $request->user()->currentAccessToken()->delete();
    return response()->json([
        'responseCode' => '200',
        'responseMessage' => 'User Logout Successfully',
        'responseType' => 'success',
    ], 200);
}
}
