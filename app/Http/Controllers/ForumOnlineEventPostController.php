<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ForumOnlineEventPost;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Hashtag;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;


class ForumOnlineEventPostController extends Controller
{
    public function index()
    {
        try{
        $Post = DB::table('forum_online_event_post')
        ->join('users', 'forum_online_event_post.user_id', '=', 'users.id')
        ->select('forum_online_event_post.id','users.name', 'users.profile', 'forum_online_event_post.created_at',
                 'forum_online_event_post.image', 'forum_online_event_post.event_link',
                 'forum_online_event_post.event_name','forum_online_event_post.event_date_time',
                 'forum_online_event_post.description','forum_online_event_post.speakers',
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
            'image' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'event_link' => 'required',
            'event_name' => 'required',
            'event_date_time' => 'required',
            'description' => 'required',
            'speakers' => 'required',
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
    
        // Handle file upload
        if ($request->hasFile('image')) {
            $filename = time() . "_OnlineEvent." . $request->file('image')->getClientOriginalExtension();
            $request->file('image')->storeAs('uploads', $filename, 'public');
            $file_path = 'uploads/' . $filename;
        }
    
        // Prepare the data to insert
        $data = [
            'user_id' => $userId,
            'event_link' => $request->event_link,
            'event_name' => $request->event_name,
            'event_date_time' => $request->event_date_time,
            'description' => $request->description,
            'speakers' => $request->speakers,
            'hashtags' => $request->hashtags,
            'image' => $file_path,
            'post_type' => (new ForumOnlineEventPost())->getTable(), // Dynamically set the post_type
        ];
    
        DB::beginTransaction();
        try {
            // Create the post
            $post = ForumOnlineEventPost::create($data);
    
            // Handle hashtags
            if ($request->filled('hashtags')) {
                $hashtagsArray = array_map('trim', explode(',', $request->hashtags)); // Split and trim hashtags
    
                foreach ($hashtagsArray as $hashtagName) {
                    if (!empty($hashtagName)) {
                        // Find or create the hashtag
                        $hashtag = Hashtag::firstOrCreate(['name' => $hashtagName]);
    
                        // Increment the useCount of the hashtag
                        $hashtag->increment('useCount');
    
                        // Associate the post with the hashtag
                        $post->hashtags()->attach($hashtag->id);
                    }
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
            'responseMessage' => 'Post added successfully',
            'responseType' => 'success',
            'data' => $post,
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
