<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ForumOccasionPost;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ForumOccasionPostController extends Controller
{
    public function index()

    {

        try{
        $Post = DB::table('forum_occasion_post')
        ->join('users', 'forum_occasion_post.user_id', '=', 'users.id')
        ->select('forum_occasion_post.id','users.name', 'users.profile', 'forum_occasion_post.created_at',
                 'forum_occasion_post.give_kudos', 'forum_occasion_post.position',
                 'forum_occasion_post.certification','forum_occasion_post.work_anniversary',
                 'forum_occasion_post.education_milestone',
                 );


            //'forum_normal_post.share',
            //'forum_normal_post.repost'
        $perPage = $request->perpage??15; // Number of items per page
        $paginatedData = $Post->paginate($perPage);



        $response = [
            'responseCode' => '200',

            'responseType' => 'success',
            'data' => $paginatedData->items(),
            'pagination' => [
                'total' => $paginatedData->total(),
                'per_page' => $paginatedData->perPage(),
                'current_page' => $paginatedData->currentPage(),
                'last_page' => $paginatedData->lastPage(),
            ],
        ];

        return response()->json($response, 200);

        } catch (\Exception $e) {
            // Handle any errors that may occur
            return response()->json([
                'responseCode' => 401,
                'responseMessage' => 'An error occurred: ' . $e->getMessage(),
                'responseType' => 'error'
            ], 200);
        }
    }

    public function store(Request $request){

        $validator = Validator::make($request->all(), [
            'give_kudos' => 'required',
            'position' => 'required',
            'certification' => 'required',
            'work_anniversary' => 'required',
            'education_milestone' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'responseCode' => '200',
                'responseMessage' => $validator->errors()->first(),
                'responseType' => 'fail'
            ], 200);
        } else {
            $userId = Auth::id();
            $data = [
                'user_id' => $userId,
                'give_kudos' => $request->give_kudos,
                'position' => $request->position,
                'certification' => $request->certification,
                'work_anniversary' => $request->work_anniversary,
                'education_milestone' => $request->education_milestone,
            ];

            DB::beginTransaction();
            try {
                $Post = ForumOccasionPost::create($data);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'responseCode' => '401',
                    'responseMessage' => 'Internal Server Error: ' . $e->getMessage(),
                    'responseType' => 'fail'
                ], 200);
            }

            return response()->json([
                'responseCode' => '200',
                'responseMessage' => 'Post added successfully',
                'responseType' => 'success',
                'data' => $Post,
            ], 200);
        }

    }
}
