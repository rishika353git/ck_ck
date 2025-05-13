<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Interest;
use App\Models\UserInterest;
use Illuminate\Http\Request;

class ChooseInterestController extends Controller
{
    /**
     * Display a listing of the interests.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $interests = Interest::all();
        return response()->json([
            'responseCode' => 200,
            'responseType' => 'success',
            'data' => $interests,
        ], 200);
    }

 public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'interest_ids' => 'required|json',  // Expecting a JSON formatted array
        ]);

        $interestIds = json_decode($request->interest_ids, true);

        // Update or create the user interest record
        $userInterest = UserInterest::updateOrCreate(
            ['user_id' => $request->user_id],
            ['interest_ids' => $interestIds]
        );

        return response()->json([
            'responseCode' => 200,
            'responseType' => 'success',
            'data' => $userInterest,
        ], 201);
    }

    public function show($id)
    {
        $userInterest = UserInterest::where('user_id', $id)->first();

        return response()->json([
            'responseCode' => 200,
            'responseType' => 'success',
            'data' => $userInterest,
        ], 200);
    }
}