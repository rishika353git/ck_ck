<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserInterest; 
use Illuminate\Http\Request;

class PendingStatusController extends Controller
{
    public function show($id)
    {
        // Find the user by ID
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'responseCode' => 404,
                'responseMessage' => 'User not found',
                'responseType' => 'fail'
            ], 404);
        }

        // Determine the verification status and reason
        $verificationResult = $this->determineVerificationStatus($user);
        $verificationStatus = $verificationResult['status'];
        $reason = $verificationResult['reason']; 

        // Fetch user's interests
        $userInterests = UserInterest::where('user_id', $user->id)->first();
        $interestIds = $userInterests ? $userInterests->interest_ids : [];
        $token = $user->createToken('auth-token')->plainTextToken;

        // Return the specific user data in the response
        return response()->json([
            'responseCode' => 200,
            'responseMessage' => 'User data fetched successfully',
            'responseType' => 'success',
            'user' => [
                'id' => $user->id,
                'name' => $user->name ?? '',
                'email' => $user->email ?? '',
                'mobile' => $user->mobile ?? '',
                'verificationStatus' => $verificationStatus,
                'reason' => $verificationStatus == '5' ? $reason : '', 
                'token' => $verificationStatus == '7' ? $token : '',
                'interests' => $interestIds
            ]
        ], 200);
    }

    private function determineVerificationStatus($user)
    {
        $reason = ''; 

  
        if ($user->mobile_verified == 0) {
            return ['status' => '0', 'reason' => $reason]; 
        } elseif ($user->user_roll == 0) {
            return ['status' => '1', 'reason' => $reason]; 
        } elseif (empty($user->card_front)) {
            return ['status' => '2', 'reason' => $reason]; 
        } elseif (empty($user->card_back)) {
            return ['status' => '3', 'reason' => $reason]; 
        } elseif ($user->card_verified == 0) {
            return ['status' => '4', 'reason' => $reason];
        } elseif ($user->card_verified == 1) {
            return ['status' => '7', 'reason' => $reason]; 
        } elseif ($user->card_verified == 2) {
            // Return the reason for rejected user
            return ['status' => '5', 'reason' => $user->reason]; 
        } elseif ($user->card_verified == 3) {
            return ['status' => '6', 'reason' => $reason]; 
        } else {
            return ['status' => '4', 'reason' => $reason];
        }
    }
}
