<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\NewPosition;
use App\Models\Hashtag; 
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class NewPositionController extends Controller
{
    public function store(Request $request)
    {
        try {
            // Validate the incoming request
            $validatedData = $request->validate([
                'title' => 'required|string|max:255',
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Validate image
                'hashtags' => 'nullable|string', // Validate optional hashtags field
            ]);

            // Handle image upload
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('images', 'public');
                $validatedData['path'] = $imagePath;
            }

            // Set the user_id from the authenticated user
            $validatedData['user_id'] = Auth::id(); 
            
              // Dynamically set the post_type
              $validatedData['post_type'] = (new NewPosition())->getTable(); // Dynamically sets the table name as post_type
              
            // Create a new NewPosition record
            $new_position = NewPosition::create($validatedData);

            // Process hashtags if provided
            if ($request->filled('hashtags')) {
                // Split hashtags by commas and trim spaces
                $hashtagsArray = array_map('trim', explode(',', $request->hashtags));

                foreach ($hashtagsArray as $hashtagName) {
                    // Find the hashtag or create it if it doesn't exist
                    $hashtag = Hashtag::firstOrCreate(['name' => $hashtagName]);

                    // Increment the useCount
                    $hashtag->increment('useCount');

                    // Associate the new position with the hashtag
                    $new_position->hashtags()->attach($hashtag->id);
                }
            }

            $response = [
                'responseCode' => '200',
                'responseType' => 'success',
                'data' => $new_position,
            ];

            return response()->json($response, 200);

        } catch (\Exception $e) {
            return response()->json([
                'responseCode' => '500',
                'responseMessage' => 'Failed to upload image: ' . $e->getMessage(),
                'responseType' => 'error',
                'data' => '',
            ], 500); 
        }
    }

    public function index()
    {
        try {
            // Get all new position records for the authenticated user
            $new_positions = NewPosition::where('user_id', Auth::id())->with('hashtags')->get();

            $response = [
                'responseCode' => '200',
                'responseType' => 'success',
                'data' => $new_positions,
            ];

            return response()->json($response, 200);

        } catch (\Exception $e) {
            return response()->json([
                'responseCode' => '500',
                'responseMessage' => 'Failed to retrieve records: ' . $e->getMessage(),
                'responseType' => 'error',
                'data' => '',
            ], 500); 
        }
    }
}
