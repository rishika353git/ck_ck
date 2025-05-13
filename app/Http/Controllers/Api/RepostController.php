<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Repost;

class RepostController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required|exists:forum_normal_post,id',
            'description' => 'nullable|string|max:1000', 
        ]);

        if ($validator->fails()) {
            return response()->json([
                'responseCode' => '400',
                'responseMessage' => $validator->errors()->first(),
                'responseType' => 'fail'
            ], 200);
        }

        $userId = Auth::id();

        if (!$userId) {
            return response()->json([
                'responseCode' => '401',
                'responseMessage' => 'User not authenticated',
                'responseType' => 'fail',
            ], 401);
        }

        $postId = $request->post_id;
        $description = $request->description;

        DB::beginTransaction();

        try {
            $existingRepost = Repost::where('user_id', $userId)
                ->where('post_id', $postId)
                ->first();

            if ($existingRepost) {
                return response()->json([
                    'responseCode' => '200',
                    'responseMessage' => 'Post already reposted',
                    'responseType' => 'fail'
                ], 200);
            }

            $repost = Repost::create([
                'user_id' => $userId,
                'post_id' => $postId,
                'description' => $description, // Store description
            ]);

            DB::commit();

            return response()->json([
                'responseCode' => '200',
                'responseMessage' => 'Post reposted successfully',
                'responseType' => 'success',
                'data' => $repost,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'responseCode' => '500',
                'responseMessage' => 'Internal Server Error: ' . $e->getMessage(),
                'responseType' => 'fail',
            ], 500);
        }
    }
}
