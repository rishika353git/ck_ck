<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WelcomeTeamTemp;
use App\Models\User;

class WelcomeTeamTempController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'following_id' => 'required|array',
            'following_id.*' => 'exists:users,id',
            'image' => 'required|string|max:255',
            'welcome_card_id' => 'required|exists:welcome_cardtypes,id',
            'description' => 'required|string',
            'hashtags' => 'required|string|max:255',
        ]);

        $user = User::find($request->user_id);

        if (!$user) {
            return response()->json([
                'responseCode' => '404',
                'responseType' => 'error',
                'message' => 'User not found'
            ], 404);
        }

        $followers = User::whereIn('id', $request->following_id)->pluck('name')->toArray();

        $welcometeamtemp = WelcomeTeamTemp::create([
            'user_id' => $request->user_id,
            'image' => $request->image,
            'welcome_card_id' => $request->welcome_card_id,
            'description' => $request->description,
            'hashtags' => $request->hashtags,
            'name' => $user->name, // Add the user's name
        ]);

        return response()->json([
            'responseCode' => '200',
            'responseType' => 'success',
            'data' => [
                'id' => $welcometeamtemp->id,
                'followername' => $followers,
                'image' => $welcometeamtemp->image,
                'welcome_card_id' => $welcometeamtemp->welcome_card_id,
                'description' => $welcometeamtemp->description,
                'hashtags' => $welcometeamtemp->hashtags,
                'created_at' => $welcometeamtemp->created_at,
                'updated_at' => $welcometeamtemp->updated_at,
            ]
        ], 200);
    }
}
