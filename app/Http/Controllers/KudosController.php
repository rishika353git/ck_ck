<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kudo;
use App\Models\User;
use App\Models\Hashtag;
use Illuminate\Support\Facades\Validator;

class KudosController extends Controller
{
    private $kudosCards = [
          [
            "id" => 1,
            "kudos_title" => "Outstanding Performance",
            "kudos_description" => "@OutstandingPerformance",
            "kudos_image" => "upload/card1.png",
            // "created_at" => "2024-07-31T07:47:12.000000Z",
            // "updated_at" => "2024-07-31T07:47:12.000000Z"
        ],
        [
            "id" => 2,
            "kudos_title" => "Exceptional Teamwork",
            "kudos_description" => "@ExceptionalTeamwork",
            "kudos_image" => "upload/card2.png",
            // "created_at" => "2024-08-02T05:23:24.000000Z",
            // "updated_at" => "2024-08-02T05:23:24.000000Z"
        ],
        [
            "id" => 3,
            "kudos_title" => "Great Initiative",
            "kudos_description" => "@GreatInitiative",
            "kudos_image" => "upload/card3.png",
            // "created_at" => "2024-08-03T05:55:11.000000Z",
            // "updated_at" => "2024-08-03T05:55:11.000000Z"
        ],
        [
            "id" => 4,
            "kudos_title" => "Exceptional Leadership",
            "kudos_description" => "@ExceptionalLeadership",
            "kudos_image" => "upload/card4.png",
            // "created_at" => "2024-08-03T10:39:45.000000Z",
            // "updated_at" => "2024-08-03T10:39:45.000000Z"
        ],
        [
            "id" => 5,
            "kudos_title" => "Innovative Solutions",
            "kudos_description" => "@InnovativeSolutions",
            "kudos_image" => "upload/card5.png",
            // "created_at" => "2024-08-03T10:39:45.000000Z",
            // "updated_at" => "2024-08-03T10:39:45.000000Z"
        ],
        [
            "id" => 6,
            "kudos_title" => "Creative Thinker",
            "kudos_description" => "@CreativeThinker",
            "kudos_image" => "upload/card6.png",
            // "created_at" => "2024-08-03T10:41:17.000000Z",
            // "updated_at" => "2024-08-03T10:41:17.000000Z"
        ],
    ];

    public function index()
    {
        return response()->json([
            'responseCode' => '200',
            'responseType' => 'success',
            'kudosCards' => $this->kudosCards
        ], 200);
    }

    public function getKudosById($kudosId)
    {
        foreach ($this->kudosCards as $kudos) {
            if ($kudos['id'] == $kudosId) {
                return $kudos;
            }
        }
        return '';
    }

    public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'user_id' => 'required|exists:users,id',
        'description' => 'required|string',
        'file_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'title' => 'required|string',
        'kudos_card_id' => 'nullable|integer',
        'hashtags' => 'nullable|string',
        'following_id' => 'nullable|json',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'responseCode' => '400',
            'responseType' => 'error',
            'errors' => $validator->errors()
        ], 400);
    }

    $data = $request->only([
        'user_id',
        'title',
        'description',
        'hashtags',
    ]);

    // Dynamically set the post_type to the table name or a custom value
    $data['post_type'] = (new Kudo())->getTable();  // Dynamically sets the table name as post_type

    // Fetch the predefined kudos card data based on kudos_card_id
    if ($request->filled('kudos_card_id')) {
        $kudosCard = $this->getKudosById($request->input('kudos_card_id'));

        if ($kudosCard) {
            $data['kudos_title'] = $kudosCard['kudos_title'];
            $data['kudos_description'] = $kudosCard['kudos_description'];
            $data['kudos_image'] = $kudosCard['kudos_image'];
            $data['kudos_card_id'] = $request->input('kudos_card_id');
        }
    }

    if ($request->hasFile('file_image')) {
        $fileImagePath = $request->file('file_image')->store('uploads', 'public');
        $data['file_image'] = $fileImagePath;
    }

    if ($request->filled('following_id')) {
        $followingIds = json_decode($request->input('following_id'), true);
        $data['following_ids'] = $followingIds;
    } else {
        $data['following_ids'] = [];
    }

    $data = array_filter($data, function ($value) {
        return !is_null($value);
    });

    // Create the Kudos post with the dynamically set post_type
    $kudo = Kudo::create($data);

    // Process hashtags if provided
    if ($request->filled('hashtags')) {
        $hashtagsArray = array_map('trim', explode(',', $request->hashtags));

        foreach ($hashtagsArray as $hashtagName) {
            // Find or create the hashtag
            $hashtag = Hashtag::firstOrCreate(['name' => $hashtagName]);

            // Increment the useCount
            $hashtag->increment('useCount');

            // Associate the kudos post with the hashtag
            $kudo->hashtags()->attach($hashtag->id);
        }
    }

    // Fetch the names of the following users
    $followingNames = User::whereIn('id', $data['following_ids'])->get(['name']);

    return response()->json([
        'responseCode' => '200',
        'responseType' => 'success',
        'message' => 'Kudos created successfully',
        'data' => $kudo,
        'following' => $followingNames
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
