<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Otp;
use App\Models\Profile;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Court;
use App\Models\AreaPractice;
use App\Models\Skill;

/** @OA\Info(
    *    title="Councel Connect",
    *    version="1.0.0",
    * )
    */
class UserController extends Controller
{
    public function index()
    {
        $users = User::select('name', 'email', 'mobile')->get();
        if (count($users) > 0) {
            $response = [
                'responseMessage' => count($users) . ' users found',
                'responseType' => 'success',
                'responseData' => $users,
            ];
        } else {
            $response = [
                'responseMessage' => count($users) . ' users found',
                'responseType' => 'fail',

            ];
        }
        return response()->json($response, 200);
    }

    function generateOTP()
    {
        $otp = rand(1000, 9999);
        return $otp;
    }

    public function register(Request $request)
{
    // Validating the incoming request with optimized validation rules
    $validator = Validator::make($request->all(), [
        'name' => 'required|string',
        'email' => 'required|email|unique:users,email',
        'mobile' => ['required', 'min:10', 'unique:users,mobile', 'regex:/^[1-9]\d{9}$/'],
        'password' => ['required', 'min:8', 'confirmed'],
        'password_confirmation' => 'required',
        'fcm_token' => 'required|string',
    ]);

    // Early return if validation fails
    if ($validator->fails()) {
        return response()->json([
            'responseCode' => '400',
            'responseMessage' => $validator->errors()->first(),
            'responseType' => 'fail',
        ], 200);
    }

    // Check if the mobile number already exists, earlier for efficiency
    $mobile = $request->input('mobile');
    $user = User::where('mobile', $mobile)->first();

    if ($user) {
        return response()->json([
            'responseCode' => '400',
            'responseMessage' => 'Mobile number already exists',
            'responseType' => 'fail',
        ], 200);
    }

    // Prepare user data for creation
    $data = [
        'name' => $request->input('name'),
        'email' => $request->input('email'),
        'mobile' => $request->input('mobile'),
        'password' => Hash::make($request->input('password')),
        'fcm_token' => $request->input('fcm_token'),
    ];

    // Wrap user creation and OTP generation in one transaction for better performance
    DB::beginTransaction();
    try {
        // Create the user
        $user = User::create($data);

        // Generate OTP
        $generatedOTP = $this->generateOTP();
        Otp::create([
            'mobile' => $request->input('mobile'),
            'otp' => $generatedOTP,
            'generatetime' => now()->addMinutes(10),
        ]);

        // Create profile for the user
        Profile::create([
            'user_id' => $user->id,
            'previous_experiences' => "[]",
            'description' => "not set",
            'home_courts' => "[]",
            'area_of_practice' => "[]",
            'top_5_skills' => "[]",
        ]);

        // Create wallet for the user
        Wallet::create([
            'user_id' => $user->id,
            'total_coins' => "[]",
        ]);

        // Commit transaction
        DB::commit();
    } catch (\Exception $e) {
        // Rollback on failure
        DB::rollBack();
        return response()->json([
            'responseCode' => '200',
            'responseMessage' => $e->getMessage(),
            'responseType' => 'fail',
        ], 500);
    }

    // Return success response with OTP and other data
    return response()->json([
        'responseCode' => '200',
        'responseMessage' => 'OTP Sent Successfully',
        'responseType' => 'success',
        'otp' => $generatedOTP,
        'mobile' => $request->input('mobile'),
        'fcm_token' => $request->input('fcm_token'),
    ], 200);
}



    public function verification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => ['required', 'min:10', 'regex:/^[1-9]\d{9}$/'],
            'otp' => ['required', 'min:4', 'max:4'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'responseCode' => '200',
                'responseMessage' => $validator->errors()->first(),
                'responseType' => 'fail'],
                200);
        }

        $user = User::where('mobile', $request->mobile)->first();

        if (!$user) {
            return response()->json(['responseCode' => '200','responseMessage' => 'User not found', 'responseType' => 'fail'], 200);
        }

        $otp = Otp::where('mobile', $request->mobile)->where('otp', $request->otp)->where('generatetime','>',now())->orderBy('id','desc')->first();
        if (!$otp) {
            return response()->json(['responseCode' => '200','responseMessage' => 'Invalid otp', 'responseType' => 'fail'], 200);
        }

        $token = $user->createToken('auth-token')->plainTextToken;
        DB::beginTransaction();

        try {
        	if($user->mobile_verified != 1){
            $user->mobile_verified_at = Carbon::now();
            $user->mobile_verified = 1;
        	}
            $user->remember_token = $token;
            $user->save();
            $otp->delete();
            // Wallet creation

            DB::commit();
            return response()->json([
                'responseCode' => '200',
                'responseMessage' => 'User Verified Successfully',
                'responseType' => 'success',
                'token' => $token,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'responseCode' => '200',
                'responseMessage' => 'Internal Server Error',
                'responseType' => 'fail',
                'error_msg' => $e->getMessage()], 200);
        }
    }

    public function resendotp(Request $request)
    {
        // Validate the input
        $validator = Validator::make($request->all(), [
            'mobile' => ['required', 'min:10', 'regex:/^[1-9]\d{9}$/'],
        ]);

        // Return validation errors, if any
        if ($validator->fails()) {
            return response()->json([
                'responseCode' => '200',
                'responseMessage' => $validator->messages()->first(),
                'responseType' => 'fail'
            ], 200);
        }

        // Check if the mobile number is registered
        $otpRecord = Otp::where('mobile', $request->mobile)->first();
        $currentTime = time();

        if (is_null($otpRecord)) {
            return response()->json([
                'responseCode' => '200',
                'responseMessage' => 'Please enter the registered mobile number.',
                'responseType' => 'error',
            ], 200);
        }

        $generate_otp_time = strtotime($otpRecord->generatetime);

        // Check if the OTP was generated recently
        // if ($currentTime - $generate_otp_time < 95) {
        //     return response()->json([
        //         'responseCode' => '200',
        //         'responseMessage' => 'Please try after some time.',
        //         'responseType' => 'error',
        //     ], 200);
        // }

        // Generate a new OTP and update the record
        DB::beginTransaction();
        try {
            $generatedOTP = $this->generateOTP();
            $otpRecord->otp = $generatedOTP;
            $otpRecord->generatetime = date('Y-m-d H:i:s', $currentTime + 10 * 60);
            $otpRecord->save();
            DB::commit();
        } catch (\Exception $err) {
            DB::rollBack();
            Log::error($err->getMessage());

            return response()->json([
                'responseCode' => '200',
                'responseMessage' => 'Internal Server Error',
                'responseType' => 'fail',
                'error_msg' => $err->getMessage(),
            ], 500);
        }

        return response()->json([
            'responseCode' => '200',
            'responseMessage' => 'OTP Sent Successfully',
            'responseType' => 'success',
            'otp' => $generatedOTP,
            'mobile' => $request->mobile,
        ], 200);
    }

public function currentuserdetails(Request $request) {
        $userId = Auth::id();
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'responseCode' => '404',
                'responseMessage' => 'User not found',
                'responseType' => 'error',
                'responseData' => '',
            ], 404);
        }

        return response()->json([
            'responseCode' => '200',
            'responseMessage' => 'User data',
            'responseType' => 'success',
            'responseData' => $user,
        ], 200);
    }


    public function resetpassword(Request $request){

        $validator = Validator::make($request->all(), [
            'mobile' => ['required', 'min:10', 'regex:/^[1-9]\d{9}$/'],
        ]);

        // Return validation errors, if any
        if ($validator->fails()) {
            return response()->json([
                'responseCode' => '200',
                'responseMessage' => $validator->messages()->first(),
                'responseType' => 'fail'
            ], 200);
        }

        $user = User::where('mobile', $request->mobile)->first();

        if (!$user) {
            return response()->json(['responseCode' => '200','responseMessage' => 'User not found', 'responseType' => 'fail'], 200);
        }

                $generatedOTP = $this->generateOTP();
                $otpdata = [
                    'mobile' => $request->mobile,
                    'otp' => $generatedOTP,
                    'generatetime' => date('Y-m-d H:i:s', strtotime('+10 minutes')),
                ];

                DB::beginTransaction();
                try {
                    $otp = Otp::create($otpdata);
                    DB::commit();

                    return response()->json([
                        'responseCode' => '200',
                        'responseMessage' => 'OTP Sent Successfully',
                        'responseType' => 'success',
                        'otp' => $generatedOTP,
                        'mobile' => $request->mobile,
                    ], 200);

                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json([
                        'responseCode' => '200',
                        'responseMessage' => $e->getMessage(),
                        'responseType'=>'fail',
                    ], 200);
                }
    }

    public function rpotp_verfication(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => ['required', 'min:10', 'regex:/^[1-9]\d{9}$/'],
            'otp' => ['required', 'min:4', 'max:4'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'responseCode' => '200',
                'responseMessage' => $validator->errors()->first(),
                'responseType' => 'fail'],
                200);
        }

        $user = User::where('mobile', $request->mobile)->first();

        if (!$user) {
            return response()->json(['responseCode' => '200','responseMessage' => 'User not found', 'responseType' => 'fail'], 200);
        }

        $otp = Otp::where('mobile', $request->mobile)->where('otp', $request->otp)->where('generatetime','>',now())->orderBy('id','desc')->first();
        if (!$otp) {
            return response()->json(['responseCode' => '200','responseMessage' => 'Invalid otp', 'responseType' => 'fail'], 200);
        }

        $token = $user->createToken('auth-token')->plainTextToken;
        DB::beginTransaction();

        try {
        	if($user->mobile_verified != 1){
            $user->mobile_verified_at = Carbon::now();
            $user->mobile_verified = 1;
        	}
            $user->remember_token = $token;
            $user->save();
            $otp->delete();
            // Wallet creation

            DB::commit();
            return response()->json([
                'responseCode' => '200',
                'responseMessage' => 'Resend Password Otp Verified  Successfully',
                'responseType' => 'success',
                'token' => $token,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'responseCode' => '200',
                'responseMessage' => 'Internal Server Error',
                'responseType' => 'fail',
                'error_msg' => $e->getMessage()], 200);
        }
    }

    public function updatePassword(Request $request)
    {
        // Validate the input
        $validator = Validator::make($request->all(), [
            'password' => ['required', 'min:8', 'confirmed'],
            'password_confirmation' => 'required',
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

        // Begin a database transaction
        DB::beginTransaction();
        try {
            // Find the authenticated user
            $user = User::find($userId);

            // Update the user's password
            if ($user) {
                $user->password = Hash::make($request->password);
                $user->save();

                DB::commit();
                $request->user()->currentAccessToken()->delete();
                return response()->json([
                    'responseCode' => '200',
                    'responseMessage' => 'Password updated successfully',
                    'responseType' => 'success'
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
                'responseType' => 'fail'
            ], 200);
        }
    }
    
    // getUserProfile method
    public function getUserProfile(Request $request)
    {
        try {
            // Fetch the authenticated user's profile
            
            $user = User::find($request->user()->id);
          //  dd($user);
            if (!$user) {
                return response()->json([
                    'message' => 'User not found',
                    'status' => 'fail',
                ], 404);
            }

             // Handle fields that may be stored as JSON strings or arrays
            foreach (['previous_experiences'] as $field) {
                // dd(isset($user->$field));
                if (isset($user->$field)) {
                    // If the field is not an array, attempt to decode it
                    if (!is_array($user->$field)) {
                        $decoded = json_decode($user->$field, true);
                      //  dd($decoded);
                        // Check if json_decode succeeded
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $user->$field = $decoded;
                        }
                    }
                   
                } else {
                    $user['previous_experiences'] = [];
                }
            }
    
            // Fetch related data  for home_courts:
           
              //  dd(is_array($user->home_courts));
            if (is_array($user->home_courts)) {

                $courtIds = $user->home_courts;
                //dd($user->home_courts);
                $courts = DB::table('court')->whereIn('id', $courtIds)->get(['id', 'name']);
                $user->home_courts = $courts->map(function($court) {
                    return [
                        'id' => $court->id,
                        'name' => $court->name
                    ];
                })->toArray();
            }else {
                $user->home_courts = [];
              //  dd( $user->home_courts);
            }
    
            // for area_of_practice:
            if (is_array($user->area_of_practice)) {
                $practiceAreaIds = $user->area_of_practice;
              //  dd($user->area_of_practice);
                $practices = DB::table('area_practice')->whereIn('id', $practiceAreaIds)->get(['id', 'name']);
                $user->area_of_practice = $practices->map(function($practice) {
                    return [
                        'id' => $practice->id,
                        'name' => $practice->name
                    ];
                })->toArray();
            }else {
                $user['area_of_practice'] = [];
            }
    
            // for top_5_skills:
            if (is_array($user->top_5_skills)) {
                $skillIds = $user->top_5_skills;
                $skills = DB::table('skill')->whereIn('id', $skillIds)->get(['id', 'name']);
                $user->top_5_skills = $skills->map(function($skill) {
                    return [
                        'id' => $skill->id,
                        'name' => $skill->name
                    ];
                })->toArray();
            }else {
                $user['top_5_skills'] = [];
            }
    
            // Return the user's profile
            return response()->json([
                'responseMessage' => 'User profile fetched successfully',
                'responseType' => 'success',            
                'responseCode' => '200',
                'data' => $user,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching the profile',
                'status' => 'fail',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    
    
    public function updateProfile(Request $request)
    {
        // Validate only the fields present in the request
        $validator = Validator::make($request->all(), [
            'year_of_enrollment' => 'sometimes|required|integer',
            'current_designation' => 'sometimes|required|string',
            'previous_experiences.*' => 'sometimes|required|string',
            'home_courts'=>  'nullable|json',
            'area_of_practice'=>  'nullable|json',
            'law_school' => 'sometimes|required|string',
            'batch' => 'sometimes|required|string',
            'linkedin_profile' => 'sometimes|required|url',
            'description' => 'sometimes|required|string',
            'profile_tagline' => 'sometimes|required|string',
            'top_5_skills'=>  'nullable|json',
            'total_follow' => 'sometimes|required|integer',
            'total_followers' => 'sometimes|required|integer',
            'questions_asked' => 'sometimes|required|integer',
            'answers_given' => 'sometimes|required|integer',
            'profile' => 'nullable|image|mimes:jpg|max:5120',
           'bannerImage' => 'nullable|image|mimes:jpg|max:5120',
            'reason' => 'sometimes|required|string',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'status' => 'fail',
                'errors' => $validator->errors()
            ], 400);
        }
    
        try {
            $user = User::find($request->user()->id);
            if (!$user) {
                return response()->json([
                    'message' => 'User not found.',
                    'status' => 'fail',
                ], 404);
            }
    
            // Prepare update data and cast necessary fields
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
                'profile',
                'bannerImage',
                'reason',
            ]);
    
            // profile and bannerImage fields are empty strings if not provided or invalid
            // $updateData['profile'] = isset($updateData['profile']) && trim($updateData['profile']) !== '' ? $updateData['profile'] : '';
            // $updateData['bannerImage'] = isset($updateData['bannerImage']) && trim($updateData['bannerImage']) !== '' ? $updateData['bannerImage'] : '';

            if ($request->hasFile('profile')) {

                // Store the image in the 'images' folder within the public storage
                $imagePath = $request->file('profile')->store('profiles', 'public');
              //  dd($imagePath);
                $updateData['profile'] = $imagePath; // Save the image path to the database
            }

            if ($request->hasFile('bannerImage')) {

                // Store the image in the 'images' folder within the public storage
                $imagePath = $request->file('bannerImage')->store('uploads', 'public');
              //  dd($imagePath);
                $updateData['bannerImage'] = $imagePath; // Save the image path to the database
            }
    
           // dd($request);
            if ($request->filled('home_courts')) {
                $homeCourts = json_decode($request->input('home_courts'), true);
                $updateData['home_courts'] = $homeCourts;
            } else {
                $updateData['home_courts'] = [];
            }
        
            $updateData = array_filter($updateData, function ($value) {
                return !is_null($value);
            });

            //area of practice is arrray of integer
            if ($request->filled('area_of_practice')) {
                $homeCourts = json_decode($request->input('area_of_practice'), true);
                $updateData['area_of_practice'] = $homeCourts;
            } else {
                $updateData['area_of_practice'] = [];
            }
        
            $updateData = array_filter($updateData, function ($value) {
                return !is_null($value);
            });

            if ($request->filled('top_5_skills')) {
                $homeCourts = json_decode($request->input('top_5_skills'), true);
                $updateData['top_5_skills'] = $homeCourts;
            } else {
                $updateData['top_5_skills'] = [];
            }
        
            $updateData = array_filter($updateData, function ($value) {
                return !is_null($value);
            });

    
            //'area_of_practice' is an array of integers
            if (isset($updateData['area_of_practice'])) {
                $updateData['area_of_practice'] = array_map('intval', $updateData['area_of_practice']);
            }
    
            // Update user data
            $user->update($updateData);
    
            // updated user data
            $updatedUser = User::find($request->user()->id);
    
            // Decode JSON fields for response
            foreach (['previous_experiences', 'home_courts', 'area_of_practice', 'top_5_skills'] as $field) {
                if (isset($updatedUser->$field)) {
                    // Only decode if it's a JSON string, not an array
                    if (is_string($updatedUser->$field)) {
                        $updatedUser->$field = json_decode($updatedUser->$field, true); 
                    }
                }
            }
            
            // Fetch court names for home_courts
            if (isset($updatedUser->home_courts)) {

                $courtIds = $updatedUser->home_courts;
               // dd($courtIds);
                $courts = DB::table('court')->whereIn('id', $courtIds)->get(['id', 'name']);
              // dd($courts);
                $updatedUser->home_courts = $courts->map(function($court) {
                    return [
                        'id' => $court->id,
                        'name' => $court->name
                    ];
                })->toArray();
            }
    
            // Fetch practice area names for area_of_practice
            if (isset($updatedUser->area_of_practice)) {
                $practiceAreaIds = $updatedUser->area_of_practice;
                $practices = DB::table('area_practice')->whereIn('id', $practiceAreaIds)->get(['id', 'name']);
    
                $updatedUser->area_of_practice = $practices->map(function($practice) {
                    return [
                        'id' => $practice->id,
                        'name' => $practice->name
                    ];
                })->toArray();
            }
    
            // Fetch skill names for top_5_skills
            if (isset($updatedUser->top_5_skills)) {
                $skillIds = $updatedUser->top_5_skills;
                $skills = DB::table('skill')->whereIn('id', $skillIds)->get(['id', 'name']);
    
                $updatedUser->top_5_skills = $skills->map(function($skill) {
                    return [
                        'id' => $skill->id,
                        'name' => $skill->name
                    ];
                })->toArray();
            }
    
           // dd($updatedUser); // Return user data
            return response()->json([
                'responseMessage' => 'Profile updated successfully.',
                'responseType' => 'success',
                'responseCode' => '200',
                'data' => $updatedUser,
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
    try {
        // Validate profile
        $request->validate([
            'profile' => 'required|image|mimes:jpeg,png,jpg,gif', 
        ]);

        // Retrieve the authenticated user
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'responseCode' => '404',
                'responseMessage' => 'User not found',
                'responseType' => 'fail'
            ], 404);
        }

        // Handle file upload if provided
        if ($request->hasFile('profile')) {
            // Store the uploaded profile image in the 'profiles' directory within the 'public' disk
            $profilePath = $request->file('profile')->store('profiles', 'public');
           // dd($request->hasFile('profile'));
            // Update the user's profile field with the new image path
            $user->profile = $profilePath;
          //dd($profilePath);
        }

        // Set card_front and card_back as empty strings if not provided
        $user->card_front = $user->card_front ?? '';
        $user->card_back = $user->card_back ?? '';
        $user->profile = $user->profile ?? '';
        $user->bannerImage = $user->bannerImage ?? '';
        $user->reason = $user->reason ?? '';

        // Save the user's updated information
        $user->save();

          // Decode JSON fields if they are not arrays already
          $user->previous_experiences = is_array($user->previous_experiences) ? $user->previous_experiences : json_decode($user->previous_experiences, true) ?? [];
          $user->top_5_skills = is_array($user->top_5_skills) ? $user->top_5_skills : json_decode($user->top_5_skills, true) ?? [];

          // Fetch and decode court data for home_courts
          if (is_array($user->home_courts)) {
              $courts = Court::whereIn('id', $user->home_courts)->get(['id', 'name']);
              $user->home_courts = $courts->toArray();
          } else {
              $user->home_courts = json_decode($user->home_courts, true) ?? [];
              $courts = Court::whereIn('id', $user->home_courts)->get(['id', 'name']);
              $user->home_courts = $courts->toArray();
          }

          // Fetch and decode area of practice data for area_of_practice
          if (is_array($user->area_of_practice)) {
              $areasOfPractice = AreaPractice::whereIn('id', $user->area_of_practice)->get(['id', 'name']);
              $user->area_of_practice = $areasOfPractice->toArray();
          } else {
              $user->area_of_practice = json_decode($user->area_of_practice, true) ?? [];
              $areasOfPractice = AreaPractice::whereIn('id', $user->area_of_practice)->get(['id', 'name']);
              $user->area_of_practice = $areasOfPractice->toArray();
          }

          if (is_array($user->top_5_skills)) {
              // If it's already an array, fetch the corresponding skills
              $skills = Skill::whereIn('id', $user->top_5_skills)->get(['id', 'name']);
              $user->top_5_skills = $skills->toArray();
          } else {
              // Try to decode JSON, default to an empty array if decoding fails
              $decodedSkills = json_decode($user->top_5_skills, true);
              
              // If decoded result is null or not an array, set it to an empty array
              if (is_null($decodedSkills) || !is_array($decodedSkills)) {
                  $decodedSkills = [];
              }
          
              // Fetch the corresponding skills based on the decoded array
              $skills = Skill::whereIn('id', $decodedSkills)->get(['id', 'name']);
              $user->top_5_skills = $skills->toArray();
          }
        
        // Return a success response with the updated user data
       // dd($user);
        return response()->json([
            'responseCode' => '200',
            'responseMessage' => 'Profile image updated successfully',
            'responseType' => 'success',
            'data' => $user
        ]);

    } catch (\Exception $e) {
        // Return an error response with the exception message
        return response()->json([
            'responseCode' => '500',
            'responseMessage' => 'Internal Server Error: ' . $e->getMessage(),
            'responseType' => 'fail'
        ], 500);
    }
}

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

    // Handle the banner image upload
    if ($request->hasFile('bannerImage')) {
        $bannerImageFilename = time() . "_banner." . $request->file('bannerImage')->getClientOriginalExtension();
        $request->file('bannerImage')->storeAs('uploads', $bannerImageFilename, 'public');
        $bannerImage = 'uploads/' . $bannerImageFilename; // Save the path to the database
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

        // Update the user's banner image if the user is found
        if ($user) {
            $user->bannerImage = $bannerImage;

            // Set card_front, card_back, profile, bannerImage, and reason as empty strings if not provided
            $user->card_front = $user->card_front ?? '';
            $user->card_back = $user->card_back ?? '';
            $user->profile = $user->profile ?? '';
            $user->reason = $user->reason ?? '';

            // Save the updated user data
            $user->save();
            DB::commit();

            // Decode JSON fields if they are not arrays already
            $user->previous_experiences = is_array($user->previous_experiences) ? $user->previous_experiences : json_decode($user->previous_experiences, true) ?? [];
            $user->top_5_skills = is_array($user->top_5_skills) ? $user->top_5_skills : json_decode($user->top_5_skills, true) ?? [];

            // Fetch and decode court data for home_courts
            if (is_array($user->home_courts)) {
                $courts = Court::whereIn('id', $user->home_courts)->get(['id', 'name']);
                $user->home_courts = $courts->toArray();
            } else {
                $user->home_courts = json_decode($user->home_courts, true) ?? [];
                $courts = Court::whereIn('id', $user->home_courts)->get(['id', 'name']);
                $user->home_courts = $courts->toArray();
            }

            // Fetch and decode area of practice data for area_of_practice
            if (is_array($user->area_of_practice)) {
                $areasOfPractice = AreaPractice::whereIn('id', $user->area_of_practice)->get(['id', 'name']);
                $user->area_of_practice = $areasOfPractice->toArray();
            } else {
                $user->area_of_practice = json_decode($user->area_of_practice, true) ?? [];
                $areasOfPractice = AreaPractice::whereIn('id', $user->area_of_practice)->get(['id', 'name']);
                $user->area_of_practice = $areasOfPractice->toArray();
            }

            if (is_array($user->top_5_skills)) {
                // If it's already an array, fetch the corresponding skills
                $skills = Skill::whereIn('id', $user->top_5_skills)->get(['id', 'name']);
                $user->top_5_skills = $skills->toArray();
            } else {
                // Try to decode JSON, default to an empty array if decoding fails
                $decodedSkills = json_decode($user->top_5_skills, true);
                
                // If decoded result is null or not an array, set it to an empty array
                if (is_null($decodedSkills) || !is_array($decodedSkills)) {
                    $decodedSkills = [];
                }
            
                // Fetch the corresponding skills based on the decoded array
                $skills = Skill::whereIn('id', $decodedSkills)->get(['id', 'name']);
                $user->top_5_skills = $skills->toArray();
            }
            

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

public function getUserById($id)
{
    try {
        // Find the user by their ID
        $user = User::find($id);

        if ($user) {
            // Handle fields that may be JSON-encoded or arrays
            foreach (['previous_experiences', 'top_5_skills'] as $field) {
                if (isset($user->$field)) {
                    // If the field is not already an array, attempt to decode it
                    if (!is_array($user->$field)) {
                        $decoded = json_decode($user->$field, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $user->$field = $decoded;
                        } else {
                            $user->$field = []; //seet toan empty array if decoding fails
                        }
                    }
                } else {
                    $user->$field = []; 
                }
            }

            // Fetch court data with id and name
            if (is_array($user->home_courts)) {
                $courts = Court::whereIn('id', $user->home_courts)->get(['id', 'name']);
                $user->home_courts = $courts->toArray();
            }

            // Fetch area_of_practice data with id and name
            if (is_array($user->area_of_practice)) {
                $areasOfPractice = AreaPractice::whereIn('id', $user->area_of_practice)->get(['id', 'name']);
                $user->area_of_practice = $areasOfPractice->toArray();
            }

              // Fetch area_of_practice data with id and name
              if (is_array($user->top_5_skills)) {
                $skills = Skill::whereIn('id', $user->top_5_skills)->get(['id', 'name']);
                $user->top_5_skills = $skills->toArray();
            }
            // Replace null or 'not set' values with empty strings
            $fieldsToReplace = [
                'current_designation', 'law_school', 'batch', 
                'linkedin_profile', 'description', 'profile_tagline', 
                'top_5_skills', 'reason', 'mobile_verified_at',
                'card_front', 'card_back' 
            ];

            foreach ($fieldsToReplace as $field) {
                if (is_null($user->$field) || $user->$field === 'not set') {
                    $user->$field = '';
                }
            }

            // Handle special cases for profile and bannerImage
            // $user->profile = $user->profile === 'profile not updated' ? '' : $user->profile;
            // $user->bannerImage = $user->bannerImage === 'profile not updated' ? '' : $user->bannerImage;

            // Determine if the user is following anyone based on following_id
            $user->is_following = !empty($user->following_id) && $user->following_id > 0 ? true : false;

            // Return success response with user data
            return response()->json([
                'responseCode' => '200',
                'responseMessage' => 'Data retrieved successfully',
                'responseType' => 'success',
                'data' => $user
            ], 200);

        } else {
            // If user not found, return 404 response
            return response()->json([
                'responseCode' => '404',
                'responseMessage' => 'User not found',
                'responseType' => 'fail'
            ], 404);
        }

    } catch (\Exception $e) {
        // Log the exception for debugging 
        Log::error($e->getMessage());

        // Return error response for internal server errors
        return response()->json([
            'responseCode' => '500',
            'responseMessage' => 'Internal Server Error: ' . $e->getMessage(),
            'responseType' => 'fail',
        ], 500);
    }
}



//added method delete it if not works
public function uploadCardImage(Request $request)
{
    // Validate the input
    $validator = Validator::make($request->all(), [
        'front' => 'required|image|mimes:jpeg,png,jpg,gif,svg|',
        'back' => 'required|image|mimes:jpeg,png,jpg,gif,svg|',
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
    if ($request->hasFile('front')) {
        $frontImageFilename = time() . "_front." . $request->file('front')->getClientOriginalExtension();
        $request->file('front')->storeAs('uploads', $frontImageFilename, 'public');
        $frontImage = 'uploads/' . $frontImageFilename; 
    } else {
        return response()->json([
            'responseCode' => '400',
            'responseMessage' => 'Front image is required.',
            'responseType' => 'fail'
        ], 200);
    }

    // Handle the back image
    if ($request->hasFile('back')) {
        $backImageFilename = time() . "_back." . $request->file('back')->getClientOriginalExtension();
        $request->file('back')->storeAs('uploads', $backImageFilename, 'public');
        $backImage = 'uploads/' . $backImageFilename; 
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
            // Update the front and back images
            $user->card_front = $frontImage;
            $user->card_back = $backImage;
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

//follow/unfollow

public function follow(Request $request)
{
    // Validate the request to ensure following_id is provided
    $request->validate([
        'following_id' => 'required|exists:users,id',
    ]);

    $follower = Auth::user(); // Get the current authenticated user
    $followingId = $request->input('following_id');

    if ($follower->id === (int)$followingId) {
        return response()->json([
            'responseCode' => '400',
            'responseType' => 'error',
            'message' => 'You cannot follow yourself'
        ], 400);
    }

    $userToFollow = User::find($followingId);

    if (!$userToFollow) {
        return response()->json([
            'responseCode' => '404',
            'responseType' => 'error',
            'message' => 'User not found'
        ], 404);
    }

    if ($follower->following_id == $followingId) {
        return response()->json([
            'responseCode' => '400',
            'responseType' => 'error',
            'message' => 'You are already following this user'
        ], 400);
    }

    // Update the `following_id` field of the current user and `follower_id` of the user being followed
    $follower->following_id = $followingId;
    $follower->save();

    $userToFollow->follower_id = $follower->id;
    $userToFollow->save();

    return response()->json([
        'responseCode' => '200',
        'responseType' => 'success',
        'message' => 'User followed successfully'
    ], 200);
}


// Unfollow a user and update the `users` table
public function unfollow(Request $request)
{
    // Validate the request to ensure following_id is provided
    $request->validate([
        'following_id' => 'required|exists:users,id',
    ]);

    $follower = Auth::user();
    $followingId = $request->input('following_id');

    if ($follower->id === (int)$followingId) {
        return response()->json([
            'responseCode' => '400',
            'responseType' => 'error',
            'message' => 'You cannot unfollow yourself'
        ], 400);
    }

    if ($follower->following_id != $followingId) {
        return response()->json([
            'responseCode' => '400',
            'responseType' => 'error',
            'message' => 'You are not following this user'
        ], 400);
    }

    // Set following_id and follower_id to null or 0 to indicate unfollow
    $follower->following_id = 0;
    $follower->save();

    $userToUnfollow = User::find($followingId);
    if ($userToUnfollow) {
        $userToUnfollow->follower_id = 0;
        $userToUnfollow->save();
    }

    return response()->json([
        'responseCode' => '200',
        'responseType' => 'success',
        'message' => 'User unfollowed successfully'
    ], 200);
}

// Get the following users' details
public function getFollowingNames(Request $request)
{
    $request->validate([
        'user_id' => 'required|exists:users,id',
    ]);

    $user = User::find($request->user_id);

    if (!$user) {
        return response()->json([
            'responseCode' => '404',
            'responseType' => 'error',
            'message' => 'User not found'
        ], 404);
    }

    // Get the following users based on the `following_id`
    $followingUsers = User::where('id', $user->following_id)->get(['id', 'name', 'email', 'profile', 'user_roll']);

    return response()->json([
        'responseCode' => '200',
        'responseType' => 'success',
        'following' => $followingUsers
    ], 200);
}

// Search following users by name
public function searchFollowingNames(Request $request)
{
    $request->validate([
        'user_id' => 'required|exists:users,id',
        'name' => 'nullable|string',
    ]);

    $user = User::find($request->user_id);

    if (!$user) {
        return response()->json([
            'responseCode' => '404',
            'responseType' => 'error',
            'message' => 'User not found'
        ], 404);
    }

    // Search for following users based on name
    $query = User::where('id', $user->following_id);

    if (!empty($request->name)) {
        $query->where('name', 'like', '%' . $request->name . '%');
    }

    $followingUsers = $query->get(['id', 'name', 'email', 'profile', 'user_roll']);

    if ($followingUsers->isEmpty()) {
        return response()->json([
            'responseCode' => '200',
            'responseType' => 'success',
            'message' => 'No following users found',
            'following' => []
        ], 200);
    }

    return response()->json([
        'responseCode' => '200',
        'responseType' => 'success',
        'following' => $followingUsers
    ], 200);
}

}