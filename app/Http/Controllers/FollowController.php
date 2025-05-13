<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class FollowController extends Controller
{
    /**
     * Follow a user.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     */
   
        public function follow(Request $request)
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
    
            if ($follower->following()->where('following_id', $followingId)->exists()) {
                return response()->json([
                    'responseCode' => '400',
                    'responseType' => 'error',
                    'message' => 'You are already following this user'
                ], 400);
            }
    
            $follower->following()->attach($followingId);
    
            return response()->json([
                'responseCode' => '200',
                'responseType' => 'success',
                'message' => 'User followed successfully'
            ], 200);
        }
    /**
     * Unfollow a user.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     */
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

        if (!$follower->following()->where('following_id', $followingId)->exists()) {
            return response()->json([
                'responseCode' => '400',
                'responseType' => 'error',
                'message' => 'You are not following this user'
            ], 400);
        }

        $follower->following()->detach($followingId);

        return response()->json([
            'responseCode' => '200',
            'responseType' => 'success',
            'message' => 'User unfollowed successfully'
        ], 200);
    }

    public function getFollowingNames(Request $request)
    {
        // Validate the request to ensure follow_id is provided
        $request->validate([
            'follow_id' => 'required|exists:users,id',
        ]);

        $user = User::find($request->follow_id);

        if (!$user) {
            return response()->json([
                'responseCode' => '404',
                'responseType' => 'error',
                'message' => 'User not found'
            ], 404);
        }

     
        $followingUsers = $user->following()->get(['name', 'email', 'profile']);

    return response()->json([
        'responseCode' => '200',
        'responseType' => 'success',
        'following' => $followingUsers
    ], 200);
}
public function searchFollowingNames(Request $request)
{
    // Validate the request to ensure follow_id is provided
    $request->validate([
        'follow_id' => 'required|exists:users,id',
        'name' => 'nullable|string'
    ]);

    $user = User::find($request->follow_id);

    if (!$user) {
        return response()->json([
            'responseCode' => '404',
            'responseType' => 'error',
            'message' => 'User not found'
        ], 404);
    }

    // Prepare the query to get the users the specified user is following
    $query = $user->following();

    // If name is provided, filter by name
    if (!empty($request->name)) {
        $query->where('name', 'like', '%' . $request->name . '%');
    }

    $followingUsers = $query->get(['name', 'email', 'profile']);

    if ($followingUsers->isEmpty()) {
        return response()->json([
            'responseCode' => '200',
            'responseType' => 'success',
            'message' => 'User not found',
            'following' =>[]
        ], 200);
    }

    return response()->json([
        'responseCode' => '200',
        'responseType' => 'success',
        'following' => $followingUsers
    ], 200);
}

}
