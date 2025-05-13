<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        $profile = Profile::where('user_id', $userId)->first();

        if ($profile) {
            // Fetch user details
            $user = User::find($userId);

            // Decode the previous_experiences field
            $profile->previous_experiences = $this->parseAssociativeArray($profile->previous_experiences);

            // Decode the other JSON-encoded fields
            $profile->home_courts = json_decode($profile->home_courts, true);
            $profile->area_of_practice = json_decode($profile->area_of_practice, true);
            $profile->top_5_skills = json_decode($profile->top_5_skills, true);

            // Add user details to profile
            $profile->name = $user->name;
            $profile->email = $user->email;
            $profile->mobile = $user->mobile;
            $profile->profile = $user->profile;
            $profile->bannerImage = $user->bannerImage;
        }

        $response = [
            'responseCode' => '200',
            'responseMessage' => 'Profile Get Successfully',
            'responseType' => 'success',
            'data' => $profile ? [$profile] : []
        ];

        return response()->json($response, 200);
    }

    public function show($userId)
    {
        $profile = Profile::where('user_id', $userId)->first();

        if ($profile) {
            // Fetch user details
            $user = User::find($userId);

            // Decode the previous_experiences field
            $profile->previous_experiences = $this->parseAssociativeArray($profile->previous_experiences);

            // Decode the other JSON-encoded fields
            $profile->home_courts = json_decode($profile->home_courts, true);
            $profile->area_of_practice = json_decode($profile->area_of_practice, true);
            $profile->top_5_skills = json_decode($profile->top_5_skills, true);

            // Add user details to profile
            $profile->name = $user->name;
            $profile->email = $user->email;
            $profile->mobile = $user->mobile;
            $profile->profile = $user->profile;
            $profile->bannerImage = $user->bannerImage;
        }

        $response = [
            'responseCode' => '200',
            'responseMessage' => 'Profile Get Successfully',
            'responseType' => 'success',
            'data' => $profile ? [$profile] : []
        ];

        return response()->json($response, 200);
    }

    private function parseAssociativeArray($string)
    {
        // Remove any unwanted characters
        $string = trim($string, '[];');

        // Replace PHP-style array syntax with JSON syntax
        $string = str_replace(' => ', ':', $string);
        $string = str_replace("\n", '', $string);

        // Convert to JSON format for json_decode
        $json = '[' . $string . ']';

        // Decode JSON to associative array
        return json_decode($json, true);
    }

   public function update(Request $request)
    {
        // Check if the user is authenticated
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'message' => 'User not authenticated.',
                'status' => 'fail',
            ], 401);
        }

        // Validate only the fields present in the request
        $validator = Validator::make($request->all(), [
            'year_of_enrollment' => 'sometimes|required|integer',
            'current_designation' => 'sometimes|required|string',
            'previous_experiences' => 'sometimes|required|string', // Use 'string' for JSON text
            'home_courts' => 'sometimes|required|string', // Use 'string' for JSON text
            'area_of_practice' => 'sometimes|required|string', // Use 'string' for JSON text
            'law_school' => 'sometimes|required|string',
            'batch' => 'sometimes|required|string',
            'linkedin_profile' => 'sometimes|required|url',
            'description' => 'sometimes|required|string',
            'profile_tagline' => 'sometimes|required|string',
            'top_5_skills' => 'sometimes|required|string', // Use 'string' for JSON text
            'total_follow' => 'sometimes|required|integer',
            'total_followers' => 'sometimes|required|integer',
            'questions_asked' => 'sometimes|required|integer',
            'answers_given' => 'sometimes|required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'status' => 'fail',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            // Debug statement to check user ID
            $userId = $user->id;
            \Log::info('Authenticated user ID: ' . $userId);

            // Retrieve the profile
            $profile = Profile::where('user_id', $userId)->first();
            if (!$profile) {
                return response()->json([
                    'message' => 'Profile not found for user ID: ' . $userId,
                    'status' => 'fail',
                ], 404);
            }

            // Prepare update data
            $updateData = $request->only([
                'year_of_enrollment',
                'current_designation',
                'previous_experiences',
                'home_courts',
                'area_of_practice',
                'law_school',
                'batch',
                'linkedin_profile',
                'description',
                'profile_tagline',
                'top_5_skills',
                'total_follow',
                'total_followers',
                'questions_asked',
                'answers_given',
            ]);

            // Convert JSON text fields to arrays
            foreach (['previous_experiences', 'home_courts', 'area_of_practice', 'top_5_skills'] as $field) {
                if (isset($updateData[$field])) {
                    $updateData[$field] = json_encode(json_decode($updateData[$field], true));
                }
            }

            $profile->update($updateData);

            // Get the full updated profile data
            $updatedProfile = Profile::where('user_id', $userId)->first();

            // Decode JSON fields for response
            foreach (['previous_experiences', 'home_courts', 'area_of_practice', 'top_5_skills'] as $field) {
                if (isset($updatedProfile->$field)) {
                    $updatedProfile->$field = json_decode($updatedProfile->$field, true);
                }
            }

            return response()->json([
                'message' => 'Profile updated successfully.',
                'status' => 'success',
                'data' => $updatedProfile,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'status' => 'fail',
            ], 500);
        }
    }

    public function updateProfileImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profile' => 'required|image|mimes:jpeg,png,jpg,gif,svg',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'responseCode' => '400',
                'responseMessage' => $validator->errors()->first(),
                'responseType' => 'fail'
            ], 400);
        }

        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'responseCode' => '500',
                    'responseMessage' => 'Internal Server Error: User not found',
                    'responseType' => 'fail'
                ], 500);
            }

            $file = $request->file('profile');
            $profilePath = 'uploads/' . time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads'), $profilePath);

            $user->profile = $profilePath;
            $user->save();

            return response()->json([
                'responseCode' => '200',
                'responseMessage' => 'Profile Image uploaded successfully',
                'responseType' => 'success',
                'data' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'responseCode' => '500',
                'responseMessage' => 'Internal Server Error: ' . $e->getMessage(),
                'responseType' => 'fail'
            ], 500);
        }
    }

   public function userRoll(Request $request)
{
    // Validate the input
    $validator = Validator::make($request->all(), [
        'roll' => 'required',
    ]);

    // Return validation errors, if any
    if ($validator->fails()) {
        return response()->json([
            'responseCode' => '200',
            'responseMessage' => $validator->errors()->first(),
            'responseType' => 'fail'
        ], 200);
    }

    // Get the authenticated user's ID
    $userId = Auth::id();

    // Prepare the data for update
    $data = [
        'roll' => $request->roll,
    ];

    // Begin a database transaction
    DB::beginTransaction();
    try {
        // Find the user by their ID
        $user = User::find($userId);

        // Update the user's roll if the user is found
        if ($user) {
            $user->user_roll = $data['roll'];
            $user->save();
            DB::commit();
            
           // Convert null or "not set" values to empty strings
            $userArray = $user->toArray();
            array_walk_recursive($userArray, function (&$item) {
                if ($item === null || $item === 'not set') {
                    $item = '';
                }
            });

            return response()->json([
                'responseCode' => '200',
                'responseMessage' => 'User roll updated successfully',
                'responseType' => 'success',
                'data' => $userArray
            ], 200);
        } else {
            throw new \Exception('User not found');
        }
    } catch (\Exception $e) {
        // Rollback the transaction on error
        DB::rollBack();
        // Log the error message
        Log::error($e->getMessage());

        return response()->json([
            'responseCode' => '500',
            'responseMessage' => 'Internal Server Error: ' . $e->getMessage(),
            'responseType' => 'fail',
        ], 500);
    }
}


    public function uploadCardImage(Request $request)
    {
        // Validate the input
        $validator = Validator::make($request->all(), [
            'card_image_front' => 'required|image|mimes:jpeg,png,jpg,gif,svg',
            'card_image_back' => 'required|image|mimes:jpeg,png,jpg,gif,svg',
        ]);

        // Return validation errors, if any
        if ($validator->fails()) {
            return response()->json([
                'responseCode' => '400',
                'responseMessage' => $validator->errors()->first(),
                'responseType' => 'fail'
            ], 200);
        }

        // Handle the front image
        if ($request->hasFile('card_image_front')) {
            $frontImageFilename = time() . "_front." . $request->file('card_image_front')->getClientOriginalExtension();
            $request->file('card_image_front')->storeAs('uploads', $frontImageFilename, 'public');
            $frontImage = 'uploads/' . $frontImageFilename; // Save path to the database
        } else {
            return response()->json([
                'responseCode' => '400',
                'responseMessage' => 'Front image is required.',
                'responseType' => 'fail'
            ], 200);
        }

        // Handle the back image
        if ($request->hasFile('card_image_back')) {
            $backImageFilename = time() . "_back." . $request->file('card_image_back')->getClientOriginalExtension();
            $request->file('card_image_back')->storeAs('uploads', $backImageFilename, 'public');
            $backImage = 'uploads/' . $backImageFilename; // Save path to the database
        } else {
            return response()->json([
                'responseCode' => '400',
                'responseMessage' => 'Back image is required.',
                'responseType' => 'fail'
            ], 200);
        }

        // Get the authenticated user's ID
        $userId = Auth::id();

        // Begin a database transaction
        DB::beginTransaction();
        try {
            // Find the user by their ID
            $user = User::find($userId);

            // Update the user's card images if user is found
            if ($user) {
                $user->card_image_front = $frontImage;
                $user->card_image_back = $backImage;
                $user->save();
                DB::commit();

                return response()->json([
                    'responseCode' => '200',
                    'responseMessage' => 'Card Images uploaded successfully',
                    'responseType' => 'success',
                    'data' => $user
                ], 200);
            } else {
                throw new \Exception('User not found');
            }
        } catch (\Exception $e) {
            // Rollback the transaction on error
            DB::rollBack();
            // Log the error message
            Log::error($e->getMessage());

            return response()->json([
                'responseCode' => '500',
                'responseMessage' => 'Internal Server Error: ' . $e->getMessage(),
                'responseType' => 'fail',
            ], 500);
        }
    }
    
    //added
    public function updateBannerImage(Request $request)
{
    // Validate the input
    $validator = Validator::make($request->all(), [
        'bannerImage' => 'required|image|mimes:jpeg,png,jpg,gif,svg',
    ]);

    // Return validation errors, if any
    if ($validator->fails()) {
        return response()->json([
            'responseCode' => '400',
            'responseMessage' => $validator->errors()->first(),
            'responseType' => 'fail'
        ], 200);
    }

    // Get the authenticated user's ID
    $userId = Auth::id();

    // Handle the banner image
    if ($request->hasFile('bannerImage')) {
        $bannerImageFilename = time() . "_banner." . $request->file('bannerImage')->getClientOriginalExtension();
        $request->file('bannerImage')->storeAs('uploads', $bannerImageFilename, 'public');
        $bannerImage = 'uploads/' . $bannerImageFilename; // Save path to the database
    } else {
        return response()->json([
            'responseCode' => '400',
            'responseMessage' => 'Banner image is required.',
            'responseType' => 'fail'
        ], 200);
    }

    // Begin a database transaction
    DB::beginTransaction();
    try {
        // Find the user by their ID
        $user = User::find($userId);

        // Update the user's banner image if user is found
        if ($user) {
            $user->bannerImage = $bannerImage;
            $user->save();
            DB::commit();

            return response()->json([
                'responseCode' => '200',
                'responseMessage' => 'Banner Image uploaded successfully',
                'responseType' => 'success',
                'data' => $user
            ], 200);
        } else {
            throw new \Exception('User not found');
        }
    } catch (\Exception $e) {
        // Rollback the transaction on error
        DB::rollBack();
        // Log the error message
        Log::error($e->getMessage());

        return response()->json([
            'responseCode' => '500',
            'responseMessage' => 'Internal Server Error: ' . $e->getMessage(),
            'responseType' => 'fail',
        ], 500);
    }
}
}
