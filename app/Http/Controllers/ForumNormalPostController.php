<?php

namespace App\Http\Controllers;

use App\Models\ForumNormalPost;
use App\Models\ForumNormalPostComment;
use App\Models\ForumNormalPostCommentReaction;
use App\Models\ForumNormalPostCommentReply;
use App\Models\ForumNormalPostReaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\Hashtag;

class ForumNormalPostController extends Controller
{
    public function index()
    {
        try{
        $Post = DB::table('forum_normal_post')
        ->join('users', 'forum_normal_post.user_id', '=', 'users.id')
        ->select('forum_normal_post.id','users.name', 'users.profile', 'forum_normal_post.created_at',
                 'forum_normal_post.description', 'forum_normal_post.files','forum_normal_post.hashtags',
                 'forum_normal_post.upvote','forum_normal_post.downvote'
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
        // Validate the input
        $validator = Validator::make($request->all(), [
            'description' => 'required|string',
            'files' => 'nullable|array',
            'files.*' => 'image|mimes:jpeg,png,jpg,gif', // Add image validation rules
            'hashtags' => 'nullable|string' // Validate hashtags as a string
        ]);
    
        // Return validation errors, if any
        if ($validator->fails()) {
            return response()->json([
                'responseCode' => '400',
                'responseMessage' => $validator->errors()->first(),
                'responseType' => 'fail'
            ], 400);
        }
    
        // Get the authenticated user's ID
        $userId = Auth::id();
    
        // Handle file uploads (single or multiple)
        $fileNames = [];
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $image) {
                $imageName = time() . "_NormalPost_" . uniqid() . '.' . $image->getClientOriginalExtension();
                try {
                    $image->storeAs('uploads', $imageName, 'public');
                    $fileNames[] = 'uploads/' . $imageName;
                } catch (\Exception $e) {
                    return response()->json([
                        'responseCode' => '500',
                        'responseMessage' => 'File Upload Error: ' . $e->getMessage(),
                        'responseType' => 'fail'
                    ], 500);
                }
            }
        }
    
        $images = json_encode($fileNames);
    
        // Convert the hashtags string to an array by splitting it
        $hashtags = $request->has('hashtags') ? explode(',', $request->hashtags) : [];
    
        // Build data array for post creation
        $data = [
            'user_id' => $userId,
            'description' => $request->description,
            'hashtags' => $request->hashtags,
            'files' => $images, // Save multiple file paths as JSON
            'post_type' => (new ForumNormalPost())->getTable() // Dynamically sets the table name as post_type
        ];
    
        DB::beginTransaction();
    
        try {
            $post = ForumNormalPost::create($data);
    
            // Handle hashtags
            if (!empty($hashtags)) {
                foreach ($hashtags as $hashtagName) {
                    $hashtagName = trim($hashtagName); // Remove any extra spaces
                    if (!empty($hashtagName)) {
                        // Find or create hashtag
                        $hashtag = Hashtag::firstOrCreate(['name' => $hashtagName]);
    
                        // Attach hashtag to the post
                        $post->hashtags()->attach($hashtag);
    
                        // Update hashtag use count
                        $hashtag->increment('useCount');
                    }
                }
            }
    
            DB::commit();
    
            return response()->json([
                'responseCode' => '200',
                'responseMessage' => 'Post added successfully',
                'responseType' => 'success',
                'data' => $post,
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

//to show hashtag
public function trendingHashtags()
{
    // Get hashtags sorted by use count in descending order
    $hashtags = Hashtag::orderBy('useCount', 'desc')->get();

    return response()->json($hashtags);
}

  public function reaction(Request $request)
{
    // Validate request data
    $validator = Validator::make($request->all(), [
        'post_id' => 'required',
        'status' => 'required|in:0,1,2',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'responseCode' => '400',
            'responseMessage' => $validator->errors()->first(),
            'responseType' => 'fail'
        ], 200);
    }

    // Get user ID
    $userId = Auth::id();

    // Get post ID and new status from request
    $postId = $request->post_id;
    $newStatus = $request->status;

    DB::beginTransaction();

    try {
        // Fetch the post
        $post = ForumNormalPost::findOrFail($postId);

        // Fetch the latest reaction of the user for the post
        $existingRecord = ForumNormalPostReaction::where('user_id', $userId)
            ->where('post_id', $postId)
            ->latest('created_at')
            ->first();

        // Check if the existing record's status matches the new status
        if ($existingRecord && $existingRecord->status == $newStatus) {
            return response()->json([
                'responseCode' => '200',
                'responseMessage' => 'Error: Status is already set to ' . $newStatus,
                'responseType' => 'fail',
            ], 400);
        }

        // Update post reactions based on existing record and new status
        if ($existingRecord) {
            if ($existingRecord->status == 1) {  // User previously upvoted
                if ($newStatus == 2) {  // Change to downvote
                    $post->upvote--;
                    $post->downvote++;
                    $message = "like - 1 , dislike + 1";
                } elseif ($newStatus == 0) {  // Change to no reaction
                    $post->upvote--;
                    $message = "like - 1";
                }
            } elseif ($existingRecord->status == 2) {  // User previously downvoted
                if ($newStatus == 1) {  // Change to upvote
                    $post->upvote++;
                    $post->downvote--;
                    $message = "dislike - 1 , like + 1";
                } elseif ($newStatus == 0) {  // Change to no reaction
                    $post->downvote--;
                    $message = "dislike - 1";
                }
            } elseif ($existingRecord->status == 0) {  // User previously had no reaction
                if ($newStatus == 1) {  // Change to upvote
                    $post->upvote++;
                    $message = "like + 1";
                } elseif ($newStatus == 2) {  // Change to downvote
                    $post->downvote++;
                    $message = "dislike + 1";
                }
            }
            $existingRecord->update(['status' => $newStatus]);
        } else {
            // Create new reaction if no existing record found
            if ($newStatus == 1) {  // Upvote
                $post->upvote++;
                $message = "like + 1";
            } elseif ($newStatus == 2) {  // Downvote
                $post->downvote++;
                $message = "dislike + 1";
            }

            ForumNormalPostReaction::create([
                'post_id' => $postId,
                'user_id' => $userId,
                'status' => $newStatus,
            ]);
        }

        // Save the changes to the post
        $post->save();

        DB::commit();

        return response()->json([
            'responseCode' => '200',
            'responseMessage' => 'Status updated successfully',
            'responseType' => 'success',
            'post' => $post,
            'action' => $message,
        ], 200);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'responseCode' => '401',
            'responseMessage' => 'Internal Server Error: ' . $e->getMessage(),
            'responseType' => 'fail',
        ], 500);
    }
}


    public function comment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required',
            'comment' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'responseCode' => '400',
                'responseMessage' => $validator->errors()->first(),
                'responseType' => 'fail'
            ], 200);
        }

        // Get user ID
        $userId = Auth::id();

        // Get post ID and new status from request
        $post_id = $request->post_id;
        $comment = $request->comment;

        $data = [
            'user_id' => $userId,
            'post_id' => $post_id,
            'comment' => $comment,
        ];
        DB::beginTransaction();

        try {
            $commentdata = ForumNormalPostComment::create($data);
            DB::commit();
            return response()->json([
                'responseCode' => '200',
                'responseMessage' => 'Comment added Successfully',
                'responseType' => 'success',
                'responseData' => $commentdata,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'responseCode' => '401',
                'responseMessage' => 'Internal Server Error: ' . $e->getMessage(),
                'responseType' => 'fail',
                'responseData'=>[],
            ], 200);
        }


    }

   public function commentReaction(Request $request)
{
    $validator = Validator::make($request->all(), [
        'comment_id' => 'required',
        'status' => 'required|in:0,1,2',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'responseCode' => '400',
            'responseMessage' => $validator->errors()->first(),
            'responseType' => 'fail'
        ], 200);
    }

    // Get user ID
    $userId = Auth::id();
    // Get comment ID and new status from request
    $commentId = $request->comment_id;
    $newStatus = $request->status;

    DB::beginTransaction();

    try {
        // Fetch the comment
        $comment = ForumNormalPostComment::findOrFail($commentId);

        // Fetch the latest reaction of the user for the comment
        $existingRecord = ForumNormalPostCommentReaction::where('comment_id', $commentId)
            ->where('user_id', $userId)
            ->latest('created_at')
            ->first();

        $message = '';

        // Check if the existing record's status matches the new status
        if ($existingRecord && $existingRecord->status == $newStatus) {
            return response()->json([
                'responseCode' => '200',
                'responseMessage' => 'Status is already set to ' . $newStatus,
                'responseType' => 'success',
            ], 200);
        }

        // Update comment reactions based on existing record and new status
        if ($existingRecord) {
            switch ($existingRecord->status) {
                case 1: // Previously upvoted
                    $comment->upvote--;
                    break;
                case 2: // Previously downvoted
                    $comment->downvote--;
                    break;
            }

            switch ($newStatus) {
                case 1: // New upvote
                    $comment->upvote++;
                    $message = "Upvoted";
                    break;
                case 2: // New downvote
                    $comment->downvote++;
                    $message = "Downvoted";
                    break;
                case 0: // Neutral
                    $message = "NO Reaction";
                    break;
            }

            $existingRecord->update(['status' => $newStatus]);
        } else {
            switch ($newStatus) {
                case 1: // New upvote
                    $comment->upvote++;
                    $message = "Upvoted";
                    break;
                case 2: // New downvote
                    $comment->downvote++;
                    $message = "Downvoted";
                    break;
                case 0: // Neutral
                    $message = "No reaction";
                    break;
            }

            ForumNormalPostCommentReaction::create([
                'comment_id' => $commentId,
                'user_id' => $userId,
                'status' => $newStatus,
            ]);
        }

        // Save the changes to the comment
        $comment->save();

        DB::commit();

        return response()->json([
            'responseCode' => '200',
            'responseMessage' => 'Status updated successfully',
            'responseType' => 'success',
            'comment' => $comment,
            'action' => $message,
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

public function showcomment(Request $request)
{
    $validator = Validator::make($request->all(), [
        'post_id' => 'required',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'responseCode' => '400',
            'responseMessage' => $validator->errors()->first(),
            'responseType' => 'fail'
        ], 200);
    }

    try {
        $post_id = $request->post_id;

        // Fetch comments with user details
        $comments = DB::table('forum_normal_post_comment')
            ->join('users', 'forum_normal_post_comment.user_id', '=', 'users.id')
            ->select('forum_normal_post_comment.id', 'forum_normal_post_comment.user_id', 'users.name', 
                     'users.profile', 'forum_normal_post_comment.created_at',
                     'forum_normal_post_comment.comment',
                     'forum_normal_post_comment.upvote', 'forum_normal_post_comment.downvote')
            ->where('forum_normal_post_comment.post_id', $post_id);

        $perPage = $request->perpage ?? 15; // Number of items per page
        $paginatedData = $comments->paginate($perPage);

        // Add replies and user_vote to each comment
        $data = $paginatedData->items();
        foreach ($data as &$comment) {
            // Determine the user's vote status
            $user_vote = 0; // Default to no vote
            if (!empty($comment->upvote) && $comment->upvote > 0) {
                $user_vote = 1; // User has upvoted
            } elseif (!empty($comment->downvote) && $comment->downvote > 0) {
                $user_vote = 2; // User has downvoted
            }
            $comment->user_vote = $user_vote;

            $comment->replys = DB::table('forum_normal_post_comment_reply')
                ->join('users', 'forum_normal_post_comment_reply.user_id', '=', 'users.id')
                ->select('forum_normal_post_comment_reply.id', 'forum_normal_post_comment_reply.reply', 
                         'forum_normal_post_comment_reply.user_id', 'users.name', 'users.profile', 
                         'forum_normal_post_comment_reply.created_at')
                ->where('forum_normal_post_comment_reply.comment_id', $comment->id)
                ->get();
        }

        $response = [
            'responseCode' => '200',
            'responseType' => 'success',
            'data' => $data,
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


    public function reply(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required',
            'comment_id' => 'required',
            'reply' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'responseCode' => '400',
                'responseMessage' => $validator->errors()->first(),
                'responseType' => 'fail'
            ], 200);
        }
        // Get user ID
        $userId = Auth::id();

        // Get post ID and new status from request
        $post_id = $request->post_id;

        $comment_id = $request->comment_id;
        $reply = $request->reply;

        $data = [
            'user_id' => $userId,
            'post_id' => $post_id,
            'comment_id' => $comment_id,
            'reply' => $reply,
        ];
        DB::beginTransaction();

        try {
            $replydata = ForumNormalPostCommentReply::create($data);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'responseCode' => '200',
                'responseMessage' => 'Internal Server Error: ' . $e->getMessage(),
                'responseType' => 'fail',
            ], 200);
        }

        // Check if the post was created successfully
        if ($replydata) {
            return response()->json([
                'responseCode' => '200',
                'responseMessage' => 'Reply added Successfully',
                'responseType' => 'success',
                'responseData' => $replydata,
            ], 200);
        } else {
            return response()->json([
                'responseCode' => '200',
                'responseMessage' => 'Internal Server Error',
                'responseType' => 'fail',
                'responseData' =>[],
            ], 200);
        }
    }

    public function showreply(Request $request)
{
    // Validate the incoming request data
    $validator = Validator::make($request->all(), [
        'post_id' => 'required',
        'comment_id' => 'required',
    ]);

    // If validation fails, return a 400 response with the first error message
    if ($validator->fails()) {
        return response()->json([
            'responseCode' => '400',
            'responseMessage' => $validator->errors()->first(),
            'responseType' => 'fail'
        ], 200);
    }

    try {
        // Retrieve the comment_id from the request
        $comment_id = $request->comment_id;

        // Query the database for replies to the comment
        $replyQuery = DB::table('forum_normal_post_comment_reply')
            ->join('users', 'forum_normal_post_comment_reply.user_id', '=', 'users.id')
            ->select(
                'forum_normal_post_comment_reply.id',
                'users.name',
                'users.profile',
                'forum_normal_post_comment_reply.created_at',
                'forum_normal_post_comment_reply.reply'
            )
            ->where('forum_normal_post_comment_reply.comment_id', $comment_id);

        // Determine the number of items per page (default is 15)
        $perPage = $request->perpage ?? 15;

        // Paginate the results
        $paginatedData = $replyQuery->paginate($perPage);

        // Prepare the response data
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

        // Return the response as JSON
        return response()->json($response, 200);

    } catch (\Exception $e) {
        // Handle any errors that may occur
        return response()->json([
            'responseCode' => '401',
            'responseMessage' => 'An error occurred: ' . $e->getMessage(),
            'responseType' => 'error'
        ], 200);
    }
}



}
