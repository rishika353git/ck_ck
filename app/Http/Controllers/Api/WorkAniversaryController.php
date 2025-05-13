<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\WorkAniversary;
use App\Models\Hashtag; // Import the Hashtag model
use Illuminate\Support\Facades\Storage;

class WorkAniversaryController extends Controller
{
    public function store(Request $request)
    {
        try {
            // Validate the incoming request
            $validatedData = $request->validate([
                'title' => 'required|string|max:255',
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048', 
                'hashtags' => 'nullable|string', // Optional hashtags field
            ]);

            // Handle image upload
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('images', 'public');
                $validatedData['path'] = $imagePath;
            }

            // Set the user_id from the authenticated user
            $validatedData['user_id'] = Auth::id();
  // Dynamically set the post_type to the table name or a custom value
  $validatedData['post_type'] = (new WorkAniversary())->getTable();  // This sets the table name as post_type

            // Create a new work anniversary record
            $work_aniversary = WorkAniversary::create($validatedData);

            // Process hashtags if provided
            if ($request->filled('hashtags')) {
                // Split hashtags by commas and trim spaces
                $hashtagsArray = array_map('trim', explode(',', $request->hashtags));

                foreach ($hashtagsArray as $hashtagName) {
                    // Find the hashtag or create it if it doesn't exist
                    $hashtag = Hashtag::firstOrCreate(['name' => $hashtagName]);

                    // Increment the useCount
                    $hashtag->increment('useCount');

                    // Associate the work anniversary with the hashtag
                    $work_aniversary->hashtags()->attach($hashtag->id);
                }
            }

            $response = [
                'responseCode' => '200',
                'responseType' => 'success',
                'data' => $work_aniversary,
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
            // Get all work anniversary records for the authenticated user
            $work_anniversaries = WorkAniversary::where('user_id', Auth::id())->with('hashtags')->get();

            $response = [
                'responseCode' => '200',
                'responseType' => 'success',
                'data' => $work_anniversaries,
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
