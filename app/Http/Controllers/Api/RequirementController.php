<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Requirement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RequirementController extends Controller
{
    /**
     * List all requirements.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $requirements = Requirement::all();
        return response()->json([
            'responseCode' => '200',
            'responseMessage' => 'Requirements retrieved successfully',
            'responseType' => 'success',
            'data' => $requirements,
        ], 200);
    }

    /**
     * Store a new requirement.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    // public function store(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'job_id' => 'required|exists:forum_hiring_post,id',
    //         'title' => 'required|string|max:255',
    //         'description' => 'required|string',
    //         'location' => 'nullable|string|max:255',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'responseCode' => '400',
    //             'responseMessage' => $validator->errors()->first(),
    //             'responseType' => 'fail',
    //         ], 400);
    //     }

    //     $userId = Auth::id();
    //     if (!$userId) {
    //         return response()->json([
    //             'responseCode' => '401',
    //             'responseMessage' => 'User not authenticated',
    //             'responseType' => 'fail',
    //         ], 401);
    //     }

    //     $data = [
    //         'user_id' => $userId,
    //         'job_id' => $request->job_id,
    //     ];

    //     DB::beginTransaction();

    //     try {
    //         $requirement = Requirement::create($data);
    //         DB::commit();
    //         return response()->json([
    //             'responseCode' => '201',
    //             'responseMessage' => 'Job applied successfully',
    //             'responseType' => 'success',
    //             'data' => $requirement,
    //         ], 201);

    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return response()->json([
    //             'responseCode' => '500',
    //             'responseMessage' => 'Internal Server Error: ' . $e->getMessage(),
    //             'responseType' => 'fail',
    //         ], 500);
    //     }
    // }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'job_id' => 'required|exists:forum_hiring_post,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'location' => 'nullable|string|max:255',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'responseCode' => '400',
                'responseMessage' => $validator->errors()->first(),
                'responseType' => 'fail',
            ], 400);
        }
    
        $userId = Auth::id();
        if (!$userId) {
            return response()->json([
                'responseCode' => '401',
                'responseMessage' => 'User not authenticated',
                'responseType' => 'fail',
            ], 401);
        }
    
        $data = [
            'user_id' => $userId,
            'job_id' => $request->job_id,
            'title' => $request->title,
            'description' => $request->description,
            'location' => $request->location,
        ];
    
        DB::beginTransaction();
    
        try {
            $requirement = Requirement::create($data);
            DB::commit();
            return response()->json([
                'responseCode' => '201',
                'responseMessage' => 'Job applied successfully',
                'responseType' => 'success',
                'data' => $requirement,
            ], 201);
    
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'responseCode' => '500',
                'responseMessage' => 'Internal Server Error: ' . $e->getMessage(),
                'responseType' => 'fail',
            ], 500);
        }
    }
    
    /**
     * Get apply history.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function applyHistory()
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json([
                'responseCode' => '401',
                'responseMessage' => 'User not authenticated',
                'responseType' => 'fail',
            ], 401);
        }

        $data = DB::table('requirement_listing')
            ->join('forum_hiring_post', 'requirement_listing.job_id', '=', 'forum_hiring_post.id')
            ->select('requirement_listing.*', 'forum_hiring_post.title', 'forum_hiring_post.description')
            ->where('requirement_listing.user_id', $userId)
            ->get();

        if ($data->isNotEmpty()) {
            return response()->json([
                'responseCode' => '200',
                'responseMessage' => count($data) . ' applied jobs found',
                'responseType' => 'success',
                'data' => $data,
            ], 200);
        } else {
            return response()->json([
                'responseCode' => '200',
                'responseMessage' => 'No applied jobs found',
                'responseType' => 'success',
                'data' => [],
            ], 200);
        }
    }
}
