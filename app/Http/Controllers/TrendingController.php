<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kudo; 
use App\Models\Welcome; 
use App\Models\ForumNormalPost;
use App\Models\Certificate;
use App\Models\NewPosition;
use App\Models\Education;
use App\Models\WorkAniversary;
//use App\Models\Services;
use Carbon\Carbon;

class TrendingController extends Controller
{
    /**
     * Get all unique trending hashtags from multiple tables.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function trending()
    {
        // Get the date from one week ago
        $oneWeekAgo = Carbon::now()->subWeek();

        // Fetch all hashtags from various tables that were created/updated in the last week
        $kudoHashtags = Kudo::where('updated_at', '>=', $oneWeekAgo)->pluck('hashtags')->toArray();
        $welcomeHashtags = Welcome::where('updated_at', '>=', $oneWeekAgo)->pluck('hashtags')->toArray();
        $normalpostHashtags = ForumNormalPost::where('updated_at', '>=', $oneWeekAgo)->pluck('hashtags')->toArray();
        $certificateHashtags = Certificate::where('updated_at', '>=', $oneWeekAgo)->pluck('hashtags')->toArray();
        $newPositionHashtags = NewPosition::where('updated_at', '>=', $oneWeekAgo)->pluck('hashtags')->toArray();
        $educationHashtags = Education::where('updated_at', '>=', $oneWeekAgo)->pluck('hashtags')->toArray();
        $workanniversaryHashtags= WorkAniversary::where('updated_at', '>=', $oneWeekAgo-1)->pluck('hashtags')->toArray();
       // $servicesHashtags = Services::where('updated_at', '>=', $oneWeekAgo)->pluck('hashtags')->toArray();

        // Merge hashtags from all tables
        $allHashtags = array_merge(
            $kudoHashtags,
            $welcomeHashtags,
            $normalpostHashtags,
            $certificateHashtags,
            $newPositionHashtags,
            $educationHashtags,
            $workanniversaryHashtags,
           // $servicesHashtags
         
        );

        // Explode the hashtags by comma, flatten the array, remove empty strings, and trim whitespace
        $allHashtagsArray = array_filter(array_map('trim', explode(',', implode(',', $allHashtags))));

        // Count occurrences of each hashtag
        $hashtagCounts = array_count_values($allHashtagsArray);

        // Sort hashtags by count in descending order
        arsort($hashtagCounts);

        // Return the trending hashtags with their counts
        return response()->json([
            'responseCode' => '200',
            'responseType' => 'success',
            'trendingHashtags' => $hashtagCounts
        ], 200);
    }
}
