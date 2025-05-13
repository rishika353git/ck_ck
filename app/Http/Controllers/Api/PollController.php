<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Poll;
use App\Models\Choice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\PollReaction;
use App\Models\Hashtag;

class PollController extends Controller
{
       // POST API to create a poll with choices
       public function store(Request $request)
       {
           try {
               // Ensure the user is authenticated
               if (!Auth::check()) {
                   return response()->json([
                       'responseCode' => '401',
                       'responseMessage' => 'Unauthorized',
                       'responseType' => 'error',
                       'data' => '',
                   ], 401);
               }
       
               // Validate the request
               $validatedData = $request->validate([
                   'ask_a_question' => 'required|string|max:255',
                   'status' => 'required|integer|in:0,1,2,3',
                   'choices' => 'required|string', // Validate choices as a JSON string
                   'user_id' => 'required|integer|exists:users,id', // Validate user_id
                   'hashtags' => 'nullable|string', // Optional hashtags field
               ]);
       
               // Decode choices from JSON string to array
               $choicesArray = json_decode($validatedData['choices'], true);
       
               // Ensure choicesArray is a valid array
               if (!is_array($choicesArray)) {
                   return response()->json([
                       'responseCode' => '400',
                       'responseMessage' => 'Invalid choices format',
                       'responseType' => 'error',
                       'data' => '',
                   ], 400);
               }
       
               // Ensure choicesArray contains only strings
               foreach ($choicesArray as $choice) {
                   if (!is_string($choice)) {
                       return response()->json([
                           'responseCode' => '400',
                           'responseMessage' => 'Each choice must be a string',
                           'responseType' => 'error',
                           'data' => '',
                       ], 400);
                   }
               }
       
               // Determine poll duration in seconds based on status
               $status = $validatedData['status'];
               switch ($status) {
                   case 0:
                       $poll_duration_seconds = 1 * 24 * 60 * 60; // 1 day
                       break;
                   case 1:
                       $poll_duration_seconds = 3 * 24 * 60 * 60; // 3 days
                       break;
                   case 2:
                       $poll_duration_seconds = 7 * 24 * 60 * 60; // 7 days
                       break;
                   case 3:
                       $poll_duration_seconds = 14 * 24 * 60 * 60; // 14 days
                       break;
                   default:
                       $poll_duration_seconds = 0;
                       break;
               }
       
               // Add poll duration to the validated data
               $validatedData['poll_duration'] = $poll_duration_seconds;
               
               // Create the poll
               $poll = Poll::create($validatedData);
       
               // Create choices for the poll
               foreach ($choicesArray as $choiceTitle) {
                   $poll->choices()->create(['title' => $choiceTitle]);
               }
       
               // Optionally increment the pollsRespondCount for the newly created poll
               Poll::where('id', $poll->id)->increment('pollsRespondCount');
       
               // Optionally get the total number of polls created
               $totalPolls = Poll::count();
       
               // Process hashtags if provided
               if ($request->filled('hashtags')) {
                   // Split hashtags by commas and trim spaces
                   $hashtagsArray = array_map('trim', explode(',', $request->hashtags));
       
                   foreach ($hashtagsArray as $hashtagName) {
                       // Find or create the hashtag
                       $hashtag = Hashtag::firstOrCreate(['name' => $hashtagName]);
       
                       // Increment the useCount
                       $hashtag->increment('useCount');
       
                       // Associate the poll with the hashtag
                       $poll->hashtags()->attach($hashtag->id);
                   }
               }
       
               // Prepare the response
               $response = [
                   'responseCode' => '200',
                   'responseType' => 'success',
                   'data' => [
                       'poll' => $poll,
                       'user_id' => $validatedData['user_id'],
                       'totalPollsCreated' => $totalPolls, // Total polls count
                       'post_type' => (new Poll())->getTable() ,
                   ],
               ];
       
               return response()->json($response, 201);
           } catch (\Exception $e) {
               return response()->json([
                   'responseCode' => '500',
                   'responseMessage' => 'Failed to create poll: ' . $e->getMessage(),
                   'responseType' => 'error',
                   'data' => '',
               ], 500);
           }
       }
       
//hashtags handling 
 //to show hashtag
 public function trendingHashtags()
 {
     // Get hashtags sorted by use count in descending order
     $hashtags = Hashtag::orderBy('useCount', 'desc')->get();
 
     return response()->json($hashtags);
 }

// GET API to fetch all polls with choices
public function getPolls()
{
    try {
        // Get authenticated user ID
        $user_id = Auth::id();
        
        // Fetch polls along with their choices
        $polls = Poll::with('choices')->get();
        
        // Iterate through each poll and check if the user has responded to any choice
        foreach ($polls as $poll) {
            $userRespondedChoiceId = null; // Default is null
            
            // Iterate through choices to find if the user has responded
            foreach ($poll->choices as $choice) {
                $respondedUsers = json_decode($choice->respondedUsers, true) ?? [];
                
                // Check if the authenticated user is in the respondedUsers array
                if (in_array($user_id, $respondedUsers)) {
                    $userRespondedChoiceId = $choice->id;
                    break; // Exit the loop once we find the user's response
                }
            }
            
            // Set user_respond field in the poll object
            $poll->user_respond = $userRespondedChoiceId;
        }
        
        // Prepare the response
        $response = [
            'responseCode' => '200',
            'responseType' => 'success',
            'data' => $polls,
        ];

        return response()->json($response, 200);

    } catch (\Exception $e) {
        return response()->json([
            'responseCode' => '500',
            'responseMessage' => 'Failed to fetch polls: ' . $e->getMessage(),
            'responseType' => 'error',
            'data' => '',
        ], 500);
    }
}

//post api to fetch responds 
public function respondToChoice(Request $request, $choiceId)
{
    try {
        // Ensure the user is authenticated
        if (!Auth::check()) {
            return response()->json([
                'responseCode' => '401',
                'responseMessage' => 'Unauthorized',
                'responseType' => 'error',
                'data' => '',
            ], 401);
        }

        // Get authenticated user ID
        $user_id = Auth::id();

        // Find the choice
        $choice = Choice::findOrFail($choiceId);

        // Get the poll ID from the choice
        $poll_id = $choice->poll_id;

        // Check if the user has already responded to any choice in the same poll
        $userRespondedToPoll = Choice::where('poll_id', $poll_id)
            ->whereRaw('JSON_CONTAINS(respondedUsers, ?) = 1', [json_encode($user_id)])
            ->exists();

        if ($userRespondedToPoll) {
            return response()->json([
                'responseCode' => '400',
                'responseMessage' => 'User has already responded to this poll.',
                'responseType' => 'error',
                'data' => '',
            ], 400);
        }

        // Get the respondedUsers array or initialize it to an empty array if it's null
        $respondedUsers = json_decode($choice->respondedUsers, true) ?? [];

        // Add the user to respondedUsers array
        $respondedUsers[] = $user_id;

        // Update the choice
        $choice->update([
            'respondCount' => $choice->respondCount + 1,
            'respondedUsers' => json_encode($respondedUsers),
        ]);

        // Prepare response with respondedUsers as an array
        $response = [
            'responseCode' => '200',
            'responseType' => 'success',
            'data' => [
                'id' => $choice->id,
                'poll_id' => $choice->poll_id,
                'title' => $choice->title,
                'respondCount' => $choice->respondCount,
                'respondedUsers' => $respondedUsers, // Return as array
                'created_at' => $choice->created_at,
                'updated_at' => $choice->updated_at,
            ],
        ];

        return response()->json($response, 200);

    } catch (\Exception $e) {
        return response()->json([
            'responseCode' => '500',
            'responseMessage' => 'Failed to respond to choice: ' . $e->getMessage(),
            'responseType' => 'error',
            'data' => '',
        ], 500);
    }
}
   
//poll reaction
public function reaction(Request $request)
{
    // Validate request data
    $validator = Validator::make($request->all(), [
        'poll_id' => 'required',
        'status' => 'required|in:0,1,2',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'responseCode' => '400',
            'responseMessage' => $validator->errors()->first(),
            'responseType' => 'fail'
        ], 400);
    }

    // Get user ID
    $userId = Auth::id();

    // Get poll ID and new status from request
    $pollId = $request->poll_id;
    $newStatus = $request->status;

    DB::beginTransaction();

    try {
        // Fetch the poll
        $poll = Poll::findOrFail($pollId);

        // Fetch the latest reaction of the user for the poll
        $existingRecord = PollReaction::where('user_id', $userId)
            ->where('poll_id', $pollId)
            ->latest('created_at')
            ->first();

        $message = '';

        // Check if the existing record's status matches the new status
        if ($existingRecord && $existingRecord->status == $newStatus) {
            return response()->json([
                'responseCode' => '200',
                'responseMessage' => 'Error: Status is already set to ' . $newStatus,
                'responseType' => 'fail',
            ], 400);
        }

        // Update poll reactions based on existing record and new status
        if ($existingRecord) {
            if ($existingRecord->status == 1) {  // User previously upvoted
                if ($newStatus == 2) {  // Change to downvote
                    $poll->upvote--;
                    $poll->downvote++;
                    $message = "like - 1 , dislike + 1";
                } elseif ($newStatus == 0) {  // Change to no reaction
                    $poll->upvote--;
                    $message = "like - 1";
                }
            } elseif ($existingRecord->status == 2) {  // User previously downvoted
                if ($newStatus == 1) {  // Change to upvote
                    $poll->upvote++;
                    $poll->downvote--;
                    $message = "dislike - 1 , like + 1";
                } elseif ($newStatus == 0) {  // Change to no reaction
                    $poll->downvote--;
                    $message = "dislike - 1";
                }
            } elseif ($existingRecord->status == 0) {  // User previously had no reaction
                if ($newStatus == 1) {  // Change to upvote
                    $poll->upvote++;
                    $message = "like + 1";
                } elseif ($newStatus == 2) {  // Change to downvote
                    $poll->downvote++;
                    $message = "dislike + 1";
                }
            }
            $existingRecord->update(['status' => $newStatus]);
        } else {
            // Create new reaction if no existing record found
            if ($newStatus == 1) {  // Upvote
                $poll->upvote++;
                $message = "like + 1";
            } elseif ($newStatus == 2) {  // Downvote
                $poll->downvote++;
                $message = "dislike + 1";
            }

            PollReaction::create([
                'poll_id' => $pollId,
                'user_id' => $userId,
                'status' => $newStatus,
            ]);
        }

        // Save the changes to the poll
        $poll->save();

        DB::commit();

        return response()->json([
            'responseCode' => '200',
            'responseMessage' => 'Status updated successfully',
            'responseType' => 'success',
            'post' => $poll,
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


}