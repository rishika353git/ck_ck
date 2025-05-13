<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserInterest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class GoogleAuthController extends Controller
{
    public function googleLogin(Request $request)
    {
        $email = $request->input('email');
        $name = $request->input('name');
        $fcm_token = $request->input('fcm_token');

        // Check if the user exists
        $user = User::where('email', $email)->first();
        if ($user) {
            // User exists, log them in and update the FCM token
            $user->fcm_token = $fcm_token;
            $user->save();

            // Fetch user interests using the helper function
            $interestIds = $this->getUserInterests($user->id);

            // Log the interest IDs for debugging
            Log::info('User Interest IDs:', ['user_id' => $user->id, 'interest_ids' => $interestIds]);

            Auth::login($user);
            $token = $user->createToken('auth-token')->plainTextToken;

            // Determine the verification status
            $verificationStatus = $this->getVerificationStatus($user);

            $response = [
                'responseCode' => '200',
                'verificationStatus' => $verificationStatus,
                'responseMessage' => 'Login Successful',
                'responseType' => 'success',
                'fcm_token' => $fcm_token,
                'token' => $token,
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'mobile' => $user->mobile,
                    'profile' => $user->profile,
                    'bannerImage' => $user->bannerImage,
                    'fcm_token' => $user->fcm_token,
                    'interests' => $interestIds
                ],
            ];

            // Include the reason if the user is rejected
            if ($user->card_verified == 2) {
                $response['reason'] = $user->reason;
            }

            return response()->json($response, 200);
        } else {
            // User does not exist, create a new user
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'fcm_token' => $fcm_token,
                'mobile' => '',
                'password' => Hash::make(''), // Generate an empty or random password
            ]);

            // Log the new user creation for debugging
            Log::info('New User Created:', ['user_id' => $user->id]);

            // Log the empty interest IDs for new users
            Log::info('User Interest IDs:', ['user_id' => $user->id, 'interest_ids' => []]);

            Auth::login($user);
            $token = $user->createToken('auth-token')->plainTextToken;

            // Default verification status for new users
            $verificationStatus = $this->getVerificationStatus($user);

            $response = [
                'responseCode' => '201',
                'responseMessage' => 'User Created Successfully',
                'responseType' => 'success',
                'fcm_token' => $fcm_token,
                'token' => $token,
                'verificationStatus' => $verificationStatus,
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name ?? '',
                    'email' => $user->email ?? '',
                    'mobile' => $user->mobile ??'',
                     'profile' => $user->profile ?? '',
                    'bannerImage' => $user->bannerImage ?? '',
                    'fcm_token' => $user->fcm_token ??'',
                    'interests' => [],  // Empty interests for new users
                ],
            ];

            // Include the reason if the user is rejected
            if ($user->card_verified == 2) {
                $response['reason'] = $user->reason;
            }

            return response()->json($response, 201);
        }
    }

    private function getUserInterests($userId)
    {
        // Fetch user interests from the database
        $userInterests = UserInterest::where('user_id', $userId)->first();

        // Log the raw data retrieved from the database
        Log::info('Raw User Interests Data:', ['user_id' => $userId, 'data' => $userInterests]);

        // Check if userInterests is not null and log the interest_ids field
        if ($userInterests) {
            Log::info('Interest IDs Retrieved:', ['interest_ids' => $userInterests->interest_ids]);
            return $userInterests->interest_ids; // This should be an array
        }

        Log::warning('No User Interests Found:', ['user_id' => $userId]);
        return [];
    }

    private function getVerificationStatus($user)
    {
        if ($user->user_roll == 0) {
            return '1'; // User Role Not Verified
        } elseif ($user->card_front == "") {
            return '2'; // Front Identity Card Empty
        } elseif ($user->card_back == "") {
            return '3'; // Back Identity Card Empty
        } elseif ($user->card_verified == 0) {
            return '4'; // User Pending
        } elseif ($user->card_verified == 2) {
            return '5'; // User Rejected By Admin
        } elseif ($user->card_verified == 3) {
            return '6'; // User Blocked
        } else {
            return '7'; // Verified
        }
    }
}
