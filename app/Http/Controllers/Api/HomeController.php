<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Wallet;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index()
    {
        // Get the authenticated user's ID
        $userId = Auth::id();

        // Retrieve the user's wallet, ensuring to handle the case where the wallet might not exist
        $wallet = Wallet::where('user_id', $userId)->first();

        // Retrieve posts with user information
        $posts = DB::table('forum_normal_post')
            ->join('users', 'forum_normal_post.user_id', '=', 'users.id')
            ->select('forum_normal_post.id', 'forum_normal_post.user_id', 'users.name', 'users.profile', 'forum_normal_post.created_at',
                     'forum_normal_post.description', 'forum_normal_post.files',
                     'forum_normal_post.upvote', 'forum_normal_post.downvote',
                     'forum_normal_post.share', 'forum_normal_post.repost')
            ->get()
            ->map(function ($post) {
                // Decode the JSON-encoded files string
                $post->files = json_decode($post->files);
                return $post;
            });

        // Retrieve reposts with original post information
        $reposts = DB::table('reposts')
            ->join('forum_normal_post', 'reposts.post_id', '=', 'forum_normal_post.id')
            ->join('users', 'forum_normal_post.user_id', '=', 'users.id')
            ->where('reposts.user_id', $userId)
            ->select('reposts.id as repost_id', 'reposts.description as repost_description', 'forum_normal_post.id as original_post_id',
                     'forum_normal_post.user_id as original_user_id', 'users.name as original_user_name', 'users.profile as original_user_profile',
                     'forum_normal_post.created_at as original_post_created_at', 'forum_normal_post.description as original_post_description', 
                     'forum_normal_post.files as original_post_files', 'forum_normal_post.upvote as original_post_upvote', 
                     'forum_normal_post.downvote as original_post_downvote', 'forum_normal_post.share as original_post_share',
                     'forum_normal_post.repost as original_post_repost')
            ->get()
            ->map(function ($repost) {
                // Decode the JSON-encoded files string
                $repost->original_post_files = json_decode($repost->original_post_files);
                return $repost;
            });

        $emptywallet = 0.0;

        // Prepare the data to be returned
        $data = [
            'wallet_balance' => $wallet ? $wallet->total_coins : $emptywallet, // Handle case where wallet is null
            'posts' => $posts,
            'reposts' => $reposts, // Add the reposts data
        ];

        // Prepare the response
        $response = [
            'responseCode' => '200',
            'responseMessage' => 'User Data Get successfully',
            'responseType' => 'success',
            'responsedata' => $data,
        ];

        // Return the response as JSON
        return response()->json($response);
    }
}
