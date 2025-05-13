<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Group;

class GroupsController extends Controller
{
   
    public function index(Request $request)
    {
        //pagination
        // Retrieve paginated records
        $perPage = $request->input('per_page', 10); 
        // Current page number
        $currentPage = $request->input('page', 1); 
    
        $Groups = Group::paginate($perPage);
    
        //response structure
        $response = [
            'responseCode' => '200',
            'responseType' => 'success',
            'data' => $Groups->items(), 
            'pagination' => [
                'total' => $Groups->total(),
                'per_page' => $Groups->perPage(),
                'current_page' => $Groups->currentPage(),
                'last_page' => $Groups->lastPage(),
            ],
        ];
    
        return response()->json($response); 
    }

public function store(Request $request)
       {
           // Validate the request
           $validator = Validator::make($request->all(), [
               'name' => 'required|string|max:255',
               'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
              'description'=>'required|string',
              //'joinedMembers'=>'nullable|json',

              'joinedMembers' => 'nullable|json',
              'joinedMembers.*' => 'exists:users,id',
           ]);
       
           if ($validator->fails()) {
               return response()->json([
                   'responseCode' => '400',
                   'responseType' => 'error',
                   'errors' => $validator->errors(),
               ], 400);
           }

            // Handle logo file upload
    $imagePath = null;
    if ($request->hasFile('logo')) {
        $imagePath = $request->file('logo')->store('uploads', 'public'); // Save image in 'public/uploads'
    }
    $joinedMembers = [];
    if ($request->filled('joinedMembers')) {
       // dd($request->input('joinedMembers'));
        $joinedMembers = json_decode($request->input('joinedMembers'), true);  // Array of user IDs
//dd($joinedMembers);
        $group['joinedMembers'] = $joinedMembers;
       // dd( $group['joinedMembers'] );
    }
    $group= array_filter($group, function ($value) {
        return !is_null($value);
    });
    //dd($group);
    
    // $joinedMembersJson = json_encode($request->joinedMembers);
  //  $joinedMembers = array_map('intval', $request->joinedMembers);
//   if ($request->filled('joinedMembers')) {
//     $joinedMembers = json_decode($request->input('joinedMembers'), true);
//     $group['joinedMembers'] = $joinedMembers;
// } else {
//     $group['joinedMembers'] = [];
// }

// $group= array_filter($group, function ($value) {
//     return !is_null($value);
// });
    
    // Find the user by ID
          // $user = User::findOrFail($request->user_id);
       
           // Create a new group and fetch profile and user_roll from users table
           $group = Group::create([
               'name' => $request->name,
               'logo' =>$imagePath,
               'description' => $request->description,
            //    'joinedMembers' =>  $joinedMembersJson,
            //'joinedMembers' => json_encode($joinedMembers),
            'joinedMembers' => $joinedMembers,
           ]);
       
        
           $groupData = [
            'id' => $group->id,
            'name' => $group->name,
            'logo' =>$group->logo,
            'description' => $group->description,
            // 'joinedMembers' => json_decode($group->joinedMembers), // Return as array,
           // 'joinedMembers' => json_decode($group->joinedMembers),
           'joinedMembers' => $joinedMembers,
            'created_at' => $group->created_at,
            'updated_at' => $group->updated_at,
              // 'user_profile' => $user->profile,  
              // 'user_roll' => $user->user_roll,   
           ];
       
           return response()->json([
               'responseCode' => '200',
               'responseType' => 'success',
               'data' => [
                   'group member' => $groupData,
               ],
           ], 200);
       }
       public function show($id)
       {
           // Find the group by ID
           $group = Group::where('id', $id)->first();
       
           if (!$group) {
               return response()->json([
                   'responseCode' => '404',
                   'responseType' => 'error',
                   'message' => 'Group not found',
               ], 404);
           }
       
           // Decode the joinedMembers field (user IDs)
           $joinedMembersIds = json_decode($group->joinedMembers, true);
       
           // If joinedMembersIds is not an array or empty, set an empty array
           if (!is_array($joinedMembersIds)) {
               $joinedMembersIds = [];
           }
       
           // Fetch the user details from the users table for each user ID in joinedMembers
           $joinedMembersDetails = User::whereIn('id', $joinedMembersIds)
               ->select('id as user_id', 'name', 'profile', 'user_roll') 
               ->get();
  
           $groupData = [
               'id' => $group->id,
               'name' => $group->Name,
               'logo' => $group->logo,
               'description' => $group->description,
               'joinedMembers' => $joinedMembersDetails, // Return user detail
               'created_at' => $group->created_at,
               'updated_at' => $group->updated_at,
           ];
       
           return response()->json([
               'responseCode' => '200',
               'responseType' => 'success',
               'data' => $groupData,
           ], 200);
       }
       
    }