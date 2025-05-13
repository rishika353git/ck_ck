<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ForumPollPost;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ForumPollPostController extends Controller
{
    public function store(Request $request)
    {
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'question' => 'required',
            'option_1' => 'required',
            'option_2' => 'required',
            'duration' => 'required',
        ]);

        // If validation fails, return a response with error details
        if ($validator->fails()) {
            return response()->json([
                'responseCode' => 400,
                'responseType' => 'error',
                'responseMessage' => 'Validation Error',
                'errors' => $validator->messages()
            ], 400);
        } else {
            $userId = Auth::id();

            // Prepare the data for insertion
            $data = [
                'user_id' => $userId,
                'question' => $request->question,
                'option_1' => $request->option_1,
                'option_2' => $request->option_2,
                'option_3' => $request->option_3,
                'option_4' => $request->option_4,
                'duration' => $request->duration,
            ];

            DB::beginTransaction();
            try {
                // Insert the data into the database
                $Poll = ForumPollPost::create($data);
                DB::commit();
            } catch (\Exception $e) {
                // If an error occurs, rollback the transaction and return an error response
                DB::rollBack();
                return response()->json([
                    'responseCode' => 500,
                    'responseType' => 'error',
                    'responseMessage' => 'Internal Server Error',
                    'error' => $e->getMessage()
                ], 500);
            }

            // If the poll was successfully created, return a success response
            if ($Poll != null) {
                return response()->json([
                    'responseCode' => 200,
                    'responseType' => 'success',
                    'responseMessage' => 'Forum Poll added Successfully',
                ], 200);
            } else {
                // If the poll was not created, return an error response
                return response()->json([
                    'responseCode' => 500,
                    'responseType' => 'error',
                    'responseMessage' => 'Failed to add Forum Poll',
                ], 500);
            }
        }
    }
}
