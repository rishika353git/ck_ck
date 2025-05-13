<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Welcome;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Hashtag;


class WelcomeTeamController extends Controller
{
    private $cardtypes = [
    [
            "id" => 1,
            "card_title" => "Thank you",
            "card_description" => "@Thankyou",
            "card_image" => "upload/card1.png",
            // "created_at" => "2024-07-31T07:47:12.000000Z",
            // "updated_at" => "2024-07-31T07:47:12.000000Z"
        ],
        [
            "id" => 2,
            "card_title" => "Team Player",
            "card_description" => "@Team Player",
            "card_image" => "upload/card2.png",
            // "created_at" => "2024-08-02T05:23:24.000000Z",
            // "updated_at" => "2024-08-02T05:23:24.000000Z"
        ],
        [
            "id" => 3,
            "card_title" => "Great Job",
            "card_description" => "@Great Job",
            "card_image" => "upload/card3.png",
            // "created_at" => "2024-08-03T05:55:11.000000Z",
            // "updated_at" => "2024-08-03T05:55:11.000000Z"
        ],
        [
            "id" => 4,
            "card_title" => "Making Work fun",
            "card_description" => "@Making work fun",
            "card_image" => "upload/card4.png",
            // "created_at" => "2024-08-03T10:39:45.000000Z",
            // "updated_at" => "2024-08-03T10:39:45.000000Z"
        ],
        [
            "id" => 5,
            "card_title" => "Amazing Mentor",
            "card_description" => "@Amazing Mentor",
            "card_image" => "upload/card5.png",
            // "created_at" => "2024-08-03T10:39:45.000000Z",
            // "updated_at" => "2024-08-03T10:39:45.000000Z"
        ],
        [
            "id" => 6,
            "card_title" => "Outside the Box Thinker",
            "card_description" => "@Outside the Box Thinker",
            "card_image" => "upload/card6.png",
            // "created_at" => "2024-08-03T10:41:17.000000Z",
            // "updated_at" => "2024-08-03T10:41:17.000000Z"
        ],
    ];

    public function index()
    {
        return response()->json([
            'responseCode' => '200',
            'responseType' => 'success',
            'cardtype' => $this->cardtypes
        ], 200);
    }

public function getCardById($cardId)
{
    $cardId = (int) $cardId; //  cardId is an integer
    \Log::info('Searching for card with ID:', ['cardId' => $cardId]);
    
    foreach ($this->cardtypes as $card) {
        \Log::info('Checking card:', ['card' => $card]);
        if ((int) $card['id'] === $cardId) { // Cast card['id'] to integer
            \Log::info('Card found:', ['card' => $card]);
            return $card;
        }
    }
    \Log::info('Card not found for ID:', ['cardId' => $cardId]);
    return '';
}


    public function store(Request $request)
    {
      

        // Validation rules
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id', // Validate user_id
            'description' => 'required|string',
            'file_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'title' => 'required|string|max:255',
            'welcomes_card_id' => 'integer',
            'hashtags' => 'nullable|string',
              'following_id' => 'nullable|json'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'responseCode' => '400',
                'responseType' => 'error',
                'errors' => $validator->errors()
            ], 400);
        }

         //fetching data
        $data = $request->only([
        'user_id',
        'title',
        'description',
        'hashtags'
    ]);
     // Dynamically set the post_type to the table name or a custom value
     $data['post_type'] = (new Welcome())->getTable();  // Dynamically sets the table name as post_type
    
    //adding trending hashtags logic in welcome controller
    $welcome = Welcome::create($data);
     // Process hashtags if provided
     if ($request->filled('hashtags')) {
        // Split hashtags by commas and trim spaces
        $hashtagsArray = array_map('trim', explode(',', $request->hashtags));

        foreach ($hashtagsArray as $hashtagName) {
            //  hashtag finding or create it if doesn't exist
            $hashtag = Hashtag::firstOrCreate(['name' => $hashtagName]);

            // Increment the useCount
            $hashtag->increment('useCount');

            // Associate the kudos post with the hashtag
            $welcome->hashtags()->attach($hashtag->id);
        }
    }

    
    // Fetch the predefined welcomes card data based on welcome_card_id
    if ($request->filled('welcomes_card_id')) {
        $WelcomesCard = $this->getCardById($request->input('welcomes_card_id'));
        
        //  dd($WelcomesCard);
        if ($WelcomesCard) {
            $data['welcomes_title'] = $WelcomesCard['card_title'];
            $data['welcomes_description'] = $WelcomesCard['card_description'];
            $data['welcomes_image'] = $WelcomesCard['card_image'];
            $data['welcomes_card_id'] = $WelcomesCard['id'];
            // \Log::info(' data ID:', ['welcomes_card_id' => $data]);
        }
    }
    
    // Handle file_image upload
    if ($request->hasFile('file_image')) {
        $fileImagePath = $request->file('file_image')->store('uploads', 'public');
        $data['file_image'] = $fileImagePath;
    }
    
    //to fetch following_ids
    // Process following_id if present
    if ($request->filled('following_id')) {
        $followingIds = json_decode($request->input('following_id'), true);
        
        // Ensure $followingIds is a valid array
        if (is_array($followingIds)) {
            $data['following_ids'] = $followingIds;
            
            // Fetch the names of users based on the following_ids
            $followingNames = User::whereIn('id', $followingIds)
            ->get(['id', 'name'])
            ->map(function ($user) {
                return [
                    'user_name' => $user->name,
                    'user_id' => $user->id,
                ];
            });
        } else {
            // If invalid, set to an empty array
            $data['following_ids'] = [];
            $followingNames = collect();
        }
    } else {
        // If no following_id provided, return empty
        $data['following_ids'] = [];
        $followingNames = collect();
    }
    
    // Filter out null values from $data
    $data = array_filter($data, function ($value) {
        return !is_null($value);
    });


    
    // Ensure user_id is included
    $data['user_id'] = $request->user_id;
    
    // Save the data to the database
    $post = Welcome::create($data);
    
    return response()->json([
        'responseCode' => '200',
        'responseType' => 'success',
        'message' => 'Post created successfully',
        'data' => $post,
        'following_id'=>$followingNames
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
