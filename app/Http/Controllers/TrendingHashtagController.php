<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Hashtag;
use Illuminate\Http\JsonResponse;

class TrendingHashtagController extends Controller
{

    public function attachHashtagToPost(Request $request)
    {
        $request->validate([
            'hashtag_id' => 'required|exists:hashtags,id',
            'post_id' => 'required|exists:forum_normal_posts,id',
        ]);

        $hashtag = Hashtag::find($request->input('hashtag_id'));
        $post = ForumNormalPost::find($request->input('post_id'));

        // Attach the hashtag to the post
        $hashtag->posts()->attach($post->id, ['postable_type' => ForumNormalPost::class]);

        // Optionally, update the useCount or return a response
        $hashtag->useCount = $hashtag->posts()->count();
        $hashtag->save();

        return response()->json(['message' => 'Hashtag attached to post successfully.']);
    }
    
    public function index(): JsonResponse
    {
        // Fetch all hashtags with their total count
        $hashtags = Hashtag::withCount(['normalposts', 'kudos', 'welcome'])
            ->get()
            ->map(function ($hashtag) {
                return [
                    'name' => $hashtag->name,
                    'posts_count' => $hashtag->posts_count,
                    'events_count' => $hashtag->events_count,
                    'hirings_count' => $hashtag->hirings_count,
                    'total_count' => $hashtag->total_count, // Attribute calculated in the model
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $hashtags
        ]);
    }
}

