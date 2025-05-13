<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Card;

class CardController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'loginid' => 'required',
            'front_image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'back_image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'responseCode' => '400',
                'responseMessage' => $validator->errors()->first(),
                'responseType' => 'fail'
            ], 200);
        }
        $user = User::where('email', $request->loginid)
                    ->orWhere('mobile', $request->loginid)
                    ->first();
        if (!$user) {
            return response()->json([
                'responseCode' => '404',
                'responseMessage' => 'User not found',
                'responseType' => 'fail'
            ], 200);
        }
        // Store front image
        $front_image = $request->file('front_image')->store('images');
        // Store back image
        $back_image = $request->file('back_image')->store('images');
        // Create or update card record
        $card = Card::updateOrCreate(
            ['user_id' => $user->id],
            ['front_image' => $front_image, 'back_image' => $back_image]
        );
        return response()->json([
            'responseCode' => '200',
            'responseMessage' => 'Card images uploaded successfully',
            'responseType' => 'success',
            'data' => $card,
        ], 201);
    }
    public function updateCard(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'loginid' => 'required',
            'front_image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'back_image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'responseCode' => '400',
                'responseMessage' => $validator->errors()->first(),
                'responseType' => 'fail'
            ], 200);
        }
        $user = User::where('email', $request->loginid)
                    ->orWhere('mobile', $request->loginid)
                    ->first();
        if (!$user) {
            return response()->json([
                'responseCode' => '404',
                'responseMessage' => 'User not found',
                'responseType' => 'fail'
            ], 200);
        }
        // Handle front image upload
        if ($request->hasFile('front_image')) {
            $frontImage = $request->file('front_image')->store('cards/front', 'public');
        }
        // Handle back image upload
        if ($request->hasFile('back_image')) {
            $backImage = $request->file('back_image')->store('cards/back', 'public');
        }
        // Update or create card record
        $card = Card::updateOrCreate(
            ['user_id' => $user->id],
            ['front_image' => $frontImage, 'back_image' => $backImage]
        );
        return response()->json([
            'responseCode' => '200',
            'responseMessage' => 'Card images updated successfully',
            'responseType' => 'success',
            'data' => $card,
        ], 200);
    }
}
