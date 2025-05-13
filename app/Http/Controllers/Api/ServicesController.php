<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Services;
use App\Models\Hashtag;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ServicesController extends Controller
{
    public function index()
    {
        try{
        $Post = DB::table('services')
        ->join('users', 'services.user_id', '=', 'users.id')
        ->select('services.id','users.name', 'users.profile', 'services.created_at',
                 'services.title', 'services.need_help',
                 'services.location','services.description'
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
   public function store(Request $request)
{
    // Validate the incoming request
    $validator = Validator::make($request->all(), [
        'title' => 'required',
        'need_help' => 'required',
        'location' => 'required',
        'description' => 'required',
        'hashtags' => 'nullable|string', // Allow optional hashtags
    ]);

    // Handle validation failures
    if ($validator->fails()) {
        return response()->json([
            'responseCode' => '200',
            'responseMessage' => $validator->errors()->first(),
            'responseType' => 'fail'
        ], 200);
    }

    $userId = Auth::id();
    $file_path = 'image not set';

    // Prepare data for storing in the database
    $data = [
        'user_id' => $userId,
        'title' => $request->title,
        'need_help' => $request->need_help,
        'location' => $request->location,
        'description' => $request->description,
        'hashtags' => $request->hashtags,
    ];

     // Dynamically set the post_type to the table name or a custom value
     $data['post_type'] = (new Services())->getTable();  // Dynamically sets the table name as post_type
    

    DB::beginTransaction();
    try {
        // Create the service post
        $service = Services::create($data);

        // Process hashtags if provided
        if ($request->filled('hashtags')) {
            // Split hashtags by commas and trim spaces
            $hashtagsArray = array_map('trim', explode(',', $request->hashtags));

            foreach ($hashtagsArray as $hashtagName) {
                // Find or create the hashtag
                $hashtag = Hashtag::firstOrCreate(['name' => $hashtagName]);

                // Increment the useCount of the hashtag
                $hashtag->increment('useCount');

                // Associate the service post with the hashtag
                $service->hashtags()->attach($hashtag->id);
            }
        }

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
        'responseMessage' => 'Service added successfully',
        'responseType' => 'success',
        'data' => $service,
    ], 200);
}
//to show hashtag
public function trendingHashtags()
{
    // Get hashtags sorted by use count in descending order
    $hashtags = Hashtag::orderBy('useCount', 'desc')->get();

    return response()->json($hashtags);
}

}
