<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Poll;
use App\Models\Choice;
use App\Models\Kudo;
use App\Models\Welcome;
use App\Models\Certificate;
use App\Models\NewPosition;
use App\Models\Education;
use App\Models\WorkAniversary;
use App\Models\ForumNormalPost;
use App\Models\ForumOfflineEventPost;
use App\Models\ForumOnlineEventPost;
use App\Models\Services;



use Illuminate\Support\Facades\Auth;


class PostController extends Controller
{
    public function getPosts(Request $request)
{
    // Pagination parameters
    $page = (int) $request->get('page', 1);
    $limit = (int) $request->get('limit', 10);
    $offset = ($page - 1) * $limit;

    // Fetch all posts without pagination
    $normal_posts = $this->fetchPosts('forum_normal_post', PHP_INT_MAX, 0, false);
    $offline_event_posts = $this->fetchPosts('forum_offline_event_post', PHP_INT_MAX, 0, false);
    $online_event_posts = $this->fetchPosts('forum_online_event_post', PHP_INT_MAX, 0, false);
    $poll_posts = $this->fetchPosts('forum_poll_post', PHP_INT_MAX, 0, false);
    $kudos = $this->fetchPosts('kudos', PHP_INT_MAX, 0, false);
    $new_positions = $this->fetchPosts('new_positions', PHP_INT_MAX, 0, false);
    $new_certifications = $this->fetchPosts('certificates', PHP_INT_MAX, 0, false);
    $work_anniversary = $this->fetchPosts('workaniversary', PHP_INT_MAX, 0, false);
    $new_education_milestones = $this->fetchPosts('education', PHP_INT_MAX, 0, false);
    $welcome_to_team = $this->fetchPosts('welcomes', PHP_INT_MAX, 0, false);
    $find_an_expert = $this->fetchPosts('services', PHP_INT_MAX, 0, false);
    $polls = $this->fetchPosts('polls', PHP_INT_MAX, 0, false);
   // dd($polls);

    // Combine all posts
    $all_posts = array_merge(
        $normal_posts['data'],
        $offline_event_posts['data'],
        $online_event_posts['data'],
        $poll_posts['data'],
        $kudos['data'],
        $new_positions['data'],
        $new_certifications['data'],
        $work_anniversary['data'],
        $new_education_milestones['data'],
        $welcome_to_team['data'],
        $find_an_expert['data'],
        $polls['data']
    );
    //dd($all_posts);

    // Sort all posts by created_at field, ensuring the most recent posts are at the top
    usort($all_posts, function($a, $b) {
        $a_date = isset($a['createdAt']) ? strtotime($a['createdAt']) : 0;
        $b_date = isset($b['createdAt']) ? strtotime($b['createdAt']) : 0;
        return $b_date <=> $a_date;
    });

    // Calculate total number of posts and pagination info
    $totalPosts = count($all_posts);
    $totalPages = ceil($totalPosts / $limit);

    // Slice the array for the current page
    $currentPosts = array_slice($all_posts, $offset, $limit);

    // Fetch user data for all posts
    $user_ids = array_unique(array_column($currentPosts, 'user_id'));
    $users = User::whereIn('id', $user_ids)->get()->keyBy('id');

    // Prepare the response structure
    $response = [
        'responseCode' => '200',
        'responseMessage' => 'Posts fetched successfully',
        'responseType' => 'success',
        'data' => array_map(function ($post) use ($users) {
            return [
                'postdata' => array_merge(
                    $this->filterPostData($post),
                    ['following_members' => $this->getFollowingNames($post['user_id'])]
                ),
                'userdata' => $this->extractUserData($users->get($post['user_id'])),
            ];
        }, $currentPosts),
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total_posts' => $totalPosts,
            'total_pages' => $totalPages
        ]
    ];

    return response()->json($response, 200);
}

    // Fetch following names for a user
    private function getFollowingNames($userId)
    {
        // Fetch the JSON array of following_ids from the kudos table for the given user
        $followingIdsJson = DB::table('kudos')
            ->where('user_id', $userId)
             ->orderBy('id', 'desc') 
            ->pluck('following_ids')
            ->first();  // Since we expect a single JSON array, we use first()
    
        // Decode the JSON array into a PHP array
        $followingIds = json_decode($followingIdsJson, true);
    
        // Check if the following IDs were successfully decoded and are not empty
        if (!empty($followingIds)) {
            // Fetch the user details (id and name) for the following IDs
            $followingMembers = User::whereIn('id', $followingIds)
                ->get(['id', 'name'])
                ->map(function ($user) {
                    return [
                        'user_name' => $user->name,
                        'user_id' => $user->id,
                    ];
                })
                ->toArray();
    
            return $followingMembers;
        }
    
        // Return an empty array if there are no following IDs or the decoding fails
        return [];
    }
    

    protected function extractUserData($user)
    {
        if (!$user) {
            return [
                'name' => '',
                'email' => '',
                'profile' => '',
                'bannerImage' => '',
            ];
        }

        return [
            'name' => $user->name,
            'email' => $user->email,
            'profile' => $user->profile ?? '', // Return empty string if profile is not provided
            'bannerImage' => $user->bannerImage ?? '', // Return empty string if bannerImage is not provided
        ];
    }


    private function fetchPosts($table_name, $limit, $offset, $includeUser = false)
    {
        $query = DB::table($table_name)
            ->limit($limit)
            ->offset($offset);

        if ($includeUser) {
            $query->leftJoin('users', $table_name . '.user_id', '=', 'users.id')
                ->select(
                    $table_name . '.*',
                    'users.name',
                    'users.email',
                    'users.profile',
                    'users.bannerImage'
                );
        } else {
            $query->select($table_name . '.*');
        }

        $total = DB::table($table_name)->count();

        $posts = $query->get()
            ->map(function ($row) use ($table_name) {
                return $this->mapPost((array)$row, $table_name);
            })->toArray();

        return [
            'data' => $posts,
            'total' => $total,
        ];
    }

    public function trendinghashtags(Request $request)
    {

        // Get the date from one week ago
        $oneWeekAgo = Carbon::now()->subWeek();
    
        // Fetch full post data from various tables
        $kudoHashtags = Kudo::where('updated_at', '>=', $oneWeekAgo)->get()->toArray();
        $welcomeHashtags = Welcome::where('updated_at', '>=', $oneWeekAgo)->get()->toArray();
        $normalpostHashtags = ForumNormalPost::where('updated_at', '>=', $oneWeekAgo)->get()->toArray();
        $certificateHashtags = Certificate::where('updated_at', '>=', $oneWeekAgo)->get()->toArray();
        $newPositionHashtags = NewPosition::where('updated_at', '>=', $oneWeekAgo)->get()->toArray();
        $educationHashtags = Education::where('updated_at', '>=', $oneWeekAgo)->get()->toArray();
        $workanniversaryHashtags = WorkAniversary::where('updated_at', '>=', $oneWeekAgo)->get()->toArray();
        $servicesHashtags = Services::where('updated_at', '>=', $oneWeekAgo)->get()->toArray();
        $offlineeventHashtags = ForumOfflineEventPost::where('updated_at', '>=', $oneWeekAgo)->get()->toArray();
        $onlineeventHashtags = ForumOnlineEventPost::where('updated_at', '>=', $oneWeekAgo)->get()->toArray();
        $pollHashtags = Poll::where('updated_at', '>=', $oneWeekAgo)->get()->toArray();

        // Merge hashtags from all tables
        $allHashtags = array_merge(
            $kudoHashtags,
            $welcomeHashtags,
            $normalpostHashtags,
            $certificateHashtags,
            $newPositionHashtags,
            $educationHashtags,
            $workanniversaryHashtags,
            $servicesHashtags,
            $offlineeventHashtags,
            $onlineeventHashtags,
            $pollHashtags,
        );

        // Function to split hashtags
        $splitHashtags = function ($hashtags) {
            return preg_split('/(?=#)/', $hashtags, -1, PREG_SPLIT_NO_EMPTY);
        };
    
        // Store final structured data
        $hashtagData = [];
        $postsWithHashtags = [];
    
        // Define all possible fields with default empty values
        $allFields = [
            //common fields
            'id' => 0,
            'user_id' => 0,
            'title' => '',
            'hashtags' => [],
            'post_type' => '',
            'path' => '',
            'description' => '',
            'files' => [],
            'upvote' => 0,
            'downvote' => 0,
            'share' => 0,
            'repost' => 0,
            'created_at' => '',
            'updated_at' => '',
           

            'job_title' => '',
            'entity_name' => '',
            'workplace' => '',
            'job_location' => '',
            'job_description' => '',
            'job_type' => '',

            //celebrate
            'card_title' => '',
            'card_description' => '',
            'card_image' => '',
            'card_id' => 0,
            'image_by_user' => '',
            'following_members' => [],
            //event fields
            'event_link' => '',
            'event_name' => '',
            'venue_address'=>'',
            'event_date_time' => '',
            'event_image' => '',
            'speakers' => '',

            //polls
            'pollQuestion' => '', 
            'pollsRespondCount' => 0,
            'poll_duration' => 0,
            'choices' => [],
            'user_respond' => 0,
            'user_vote' => 0,
            
            //services
            'service_title'=>'',
            'service_location'=>'',
            // 'userdata' => [
            //     'name' => '',
            //     'email' => '',
            //     'profile' => '',
            //     'banner_image' => '',              
            // ],
            
        ];
    
        // Loop through each record, split hashtags, and group them
        foreach ($allHashtags as $item) {
            $hashtags = implode(',', $splitHashtags($item['hashtags']));
            $updatedAt = $item['updated_at'];
            $postType = $item['post_type'];
    
            // Split combined hashtags
            $splitHashtagsArray = explode(',', $hashtags);
    
            foreach ($splitHashtagsArray as $hashtag) {
                $hashtag = trim($hashtag);
                if (!empty($hashtag)) {
                    if (!isset($hashtagData[$hashtag])) {
                        $hashtagData[$hashtag] = [
                            'count' => 0,
                            'last_updated' => $updatedAt
                        ];
                    }
    
                    // Increase count
                    $hashtagData[$hashtag]['count'] += 1;
    
                    // Update the 'last_updated' if more recent
                    if ($updatedAt > $hashtagData[$hashtag]['last_updated']) {
                        $hashtagData[$hashtag]['last_updated'] = $updatedAt;
                    }
                }
            }
           // dd($item);
    
            // Collect full post data based on hashtags
            $postWithDefaultFields = array_merge($allFields, $item);
            
                // Add user data to the post if available
                if (isset($item['user_id'])) {
                    $user = User::find($item['user_id']);
                    // $postWithDefaultFields['userdata'] = [
                    //     'name' => $user->name ?? '',
                    //     'email' => $user->email ?? '',
                    //     'profile' => $user->profile_image ?? '',
                    //     'banner_image' => $user->banner_image ?? ''
                    // ];
                }

            // Adjust fields for kudo posts
            if ($postType === 'kudos') {
                $postWithDefaultFields['card_id'] = $item['kudos_card_id'] ?? 0;
                $postWithDefaultFields['card_title'] = $item['kudos_title'] ?? '';
                $postWithDefaultFields['card_description'] = $item['kudos_description'] ?? '';
                $postWithDefaultFields['card_image'] = $item['kudos_image'] ?? '';
                $postWithDefaultFields['image_by_user'] = $item['file_image'] ?? '';            
            
                // Remove old kudos fields
                unset($postWithDefaultFields['kudos_card_id']);
                unset($postWithDefaultFields['kudos_title']);
                unset($postWithDefaultFields['kudos_description']);
                unset($postWithDefaultFields['kudos_image']);
                unset($postWithDefaultFields['file_image']);
                if (isset($item['following_ids'])) {
                    // Check if following_ids is a string and decode it, or use it directly if it's already an array
                    $followingIds = is_string($item['following_ids']) ? json_decode($item['following_ids'], true) : $item['following_ids'];
                
                    // Ensure followingIds is an array
                    if (is_array($followingIds)) {
                        $followingMembers = User::whereIn('id', $followingIds)->get(['id', 'name']);
                
                        // Map the results to the desired format
                        $postWithDefaultFields['following_members'] = $followingMembers->map(function($user) {
                            return [
                                'user_id' => $user->id,
                                'user_name' => $user->name,
                            ];
                        })->toArray(); // Convert the collection to an array
                    } else {
                        $postWithDefaultFields['following_members'] = []; // Fallback
                    }
                }
            }                
            
            if ($postType === 'polls') {
                $postWithDefaultFields['pollQuestion'] = $item['ask_a_question'] ?? '';
            
                // Fetch choices related to the poll
                $pollId = $item['id'];
                $pollChoices = Choice::where('poll_id', $pollId)->get(['id', 'poll_id', 'title', 'respondCount', 'respondedUsers']);
                $choices = [];
                $totalRespondCount = 0;
            
                // Track if the user has responded
                $user_respond = 0;  
                $authUserId = auth()->id();  
            
                foreach ($pollChoices as $choice) {
                    $respondedUsers = is_string($choice->respondedUsers) ? json_decode($choice->respondedUsers, true) : ($choice->respondedUsers ?? []);
                    $respondedUsers = is_array($respondedUsers) ? $respondedUsers : [];
            
                    // Add choice data
                    $choices[] = [
                        'id' => $choice->id,
                        'poll_id' => $choice->poll_id,
                        'title' => $choice->title,
                        'respondCount' => $choice->respondCount,
                        'respondedUsers' => $respondedUsers,
                    ];
            
                    // Check if the authenticated user has responded to this choice
                    if (in_array($authUserId, $respondedUsers)) {
                        $user_respond = $choice->id;  // Set user_respond to the choice ID the user selected
                    }
            
                    $totalRespondCount += $choice->respondCount;
                }
            
                // Add poll-related data to the post
                $postWithDefaultFields['choices'] = $choices;
                $postWithDefaultFields['pollsRespondCount'] = $totalRespondCount;
                $postWithDefaultFields['user_respond'] = $user_respond;  // Include the user_respond value
            
                // Remove old poll question field
                unset($postWithDefaultFields['ask_a_question']);
            }

            if($postType === 'forum_offline_event_post'){
                $postWithDefaultFields['event_image'] = $item['image'] ?? '';

                unset($postWithDefaultFields['image']);
            }
            if($postType === 'services'){
                $postWithDefaultFields['service_title'] = $item['need_help'] ?? '';
                $postWithDefaultFields['service_location'] = $item['location'] ?? '';
                unset($postWithDefaultFields['need_help']);
                unset($postWithDefaultFields['location']);
            }
    
            $postsWithHashtags[] = [
                'post' => $postWithDefaultFields,
                'post_type' => $postType,
                'hashtags' => $item['hashtags'],
                'last_updated' => $item['updated_at']
            ];
        }
    
        // Sort hashtags by count (descending) and then last_updated (descending)
        uasort($hashtagData, function ($a, $b) {
            return $b['count'] <=> $a['count'] ?: strcmp($b['last_updated'], $a['last_updated']);
        });
    
        // Create response data for trending hashtags
        $responseData = [];
        foreach ($hashtagData as $hashtag => $data) {
            $responseData[] = [
                'hashtag' => $hashtag,
                'count' => $data['count'],
                'last_updated' => $data['last_updated'],
            ];
        }
    
        // Check if a specific hashtag is requested
        $specificHashtag = $request->input('hashtag');
        $matchedPosts = [];
    
        if ($specificHashtag) {
            // Pagination parameters
            $page = (int) $request->get('page', 1);
            $limit = (int) $request->get('limit', 10);
            $offset = ($page - 1) * $limit;
    
            // Normalize the specific hashtag (remove leading '#')
            $specificHashtag = ltrim($specificHashtag, '#');
    
            // Filter posts that contain the specific hashtag
            foreach ($postsWithHashtags as $post) {
                unset($post['post']['status']);
                unset($post['post']['following_ids']);
 
                // Process hashtags to be in array format
                $hashtagsArray = $splitHashtags($post['post']['hashtags']); 
                $hashtagsArray = array_map(fn($tag) => ltrim(trim($tag), '#'), $hashtagsArray);
                
                // Update the post's hashtags field to be an array
                $post['post']['hashtags'] = $hashtagsArray;
            
                // Check if the specific hashtag is present
                if (in_array($specificHashtag, $hashtagsArray)) {
                    // Add the processed post to the matchedPosts array
                    $matchedPosts[] = $post['post'];
                }
            }
            
            // Total posts and pagination calculation
            $totalPosts = count($matchedPosts);
            $totalPages = ceil($totalPosts / $limit);
    
            // Slice the array for the current page
            $currentPosts = array_slice($matchedPosts, $offset, $limit);
    
            // Return paginated response for posts with the specific hashtag
            return response()->json([
                'responseCode' => '200',
                'responseMessage' => 'Posts fetched successfully',
                'responseType' => 'success',
                //'data' => $currentPosts,
                // 'data' => [
                //     'postdata' => $currentPosts,
                //     'userdata' => $this->extractUserData($user)
                // ],

                'data' => array_map(function ($post) use ($user) {
                    return [
                        'postdata' => array_merge(
                            $this->filterPostData($post),
                            ['following_members' => $this->getFollowingNames($post['user_id'])]
                        ),
                        'userdata' => $this->extractUserData($user),
                    ];
                }, $currentPosts),
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total_posts' => $totalPosts,
                    'total_pages' => $totalPages
                ]
            ], 200);
        } else {
            // If no specific hashtag, return all posts containing any trending hashtags
            foreach ($postsWithHashtags as $post) {
                foreach ($responseData as $trendingHashtag) {
                    if (str_contains($post['post']['hashtags'], $trendingHashtag['hashtag'])) {
                        // Add the processed post to the matchedPosts array
                        $matchedPosts[] = $post['post'];
                    }
                }
            }
    
            // Return trending hashtags and their posts
            return response()->json([
                'responseCode' => '200',
                'responseMessage' => 'success',
                'trending_hashtags' => $responseData
            ], 200);
        }
    }
    
    // Helper function to determine the post type based on the source table
    private function getPostType($post)
    {
        // we can define our post types based on how we distinguish them
        if ($post instanceof Kudo) {
            return 'kudo';
        } elseif ($post instanceof Welcome) {
            return 'welcome';
        } elseif ($post instanceof ForumNormalPost) {
            return 'forum_normal_post';
        } elseif ($post instanceof Certificate) {
            return 'certificate';
        } elseif ($post instanceof NewPosition) {
            return 'new_position';
        } elseif ($post instanceof Education) {
            return 'education';
        } elseif ($post instanceof WorkAniversary) {
            return 'work_anniversary';
        } elseif ($post instanceof Services) {
            return 'services';
        } elseif ($post instanceof ForumOfflineEventPost) {
            return 'forum_offline_event_post';
        } elseif ($post instanceof ForumOnlineEventPost) {
            return 'forum_online_event_post';
        }
    
        return 'unknown';
    }
    //hashtags logic ends here

   private function mapPost($row, $type)
    {
            \Log::info('Mapping Post Data:', [
        'type' => $type,
        'row' => $row,
    ]);
        
        $authUserId = auth()->id();
    
       // Initialize user_vote to 0
        $user_vote = 0; // Default to no vote

        if (!empty($row['upvote']) && $row['upvote'] > 0) {
            $user_vote = 1; // User has upvoted
        } elseif (!empty($row['downvote']) && $row['downvote'] > 0) {
            $user_vote = 2; // User has downvoted
        }
    
        // Poll related logic
        $choices = [];
        $totalRespondCount = 0;
        $user_respond = 0;
    
        if ($type === 'polls' && isset($row['id'])) {
            $pollId = $row['id'];
            $pollChoices = Choice::where('poll_id', $pollId)->get(['id', 'title', 'respondCount', 'respondedUsers']);
            $loggedInUserId = $authUserId;
    
            foreach ($pollChoices as $choice) {
                $respondedUsers = is_string($choice->respondedUsers) ? json_decode($choice->respondedUsers, true) : ($choice->respondedUsers ?? []);
                $respondedUsers = is_array($respondedUsers) ? $respondedUsers : [];
    
                $choices[] = [
                    'choice_id' => $choice->id,
                    'title' => $choice->title,
                    'respondCount' => $choice->respondCount,
                    'respondedUsers' => $respondedUsers,
                ];
                $totalRespondCount += $choice->respondCount;
    
                // Check if the logged-in user has responded to this choice
                if (in_array($loggedInUserId, $respondedUsers)) {
                    $user_respond = $choice->id;
                }
            }
        }
    
        // Additional logic for options and hashtags
        $options = [];
        if (!empty($row['option_1'])) $options[] = $row['option_1'];
        if (!empty($row['option_2'])) $options[] = $row['option_2'];
        if (!empty($row['option_3'])) $options[] = $row['option_3'];
        if (!empty($row['option_4'])) $options[] = $row['option_4'];
    
        $hashtags = [];
        if (isset($row['hashtags']) && is_string($row['hashtags'])) {
            $hashtags = array_filter(array_map('trim', explode(',', $row['hashtags'])));
        }


     // Handle card_image selection
     $card_image = '';
      if (!empty($row['path'])) {
          $card_image = $row['path'];
     }elseif(!empty($row['kudos_image'])) {
          $card_image = $row['kudos_image'];
     }elseif (!empty($row['welcomes_image'])) {
          $card_image = $row['welcomes_image'];
     }
      
   // Determine card ID, title, and description based on type
$card_id = $type === 'kudos' ? ($row['kudos_card_id'] ?? null) : ($row['welcomes_card_id'] ?? null);
$card_title = $type === 'kudos' ? ($row['kudos_title'] ?? '') : ($row['welcomes_title'] ?? '');
$card_description = $type === 'kudos' ? ($row['kudos_description'] ?? '') : ($row['welcomes_description'] ?? '');

// Log card data
\Log::info('Card Data:', [
    'card_id' => $card_id,
    'card_title' => $card_title,
    'card_description' => $card_description,
]);

        // Return the final mapped post data
        return [
            'id' => $row['id'] ?? '',
            'title' => $row['title'] ?? '',
            'user_id' => $row['user_id'] ?? '',
            'description' => $row['description'] ?? '',
            'files' => !empty($row['files']) ? json_decode($row['files'], true) : [],
            'card_image' => $card_image,
            'upvote' => $row['upvote']??0, 
            'downvote' => $row['downvote']??0, 
            'share' => $row['share'] ?? 0,
            'repost' => $row['repost'] ?? 0,
            'created_at' => $row['created_at'] ?? '',
            'updated_at' => $row['updated_at'] ?? '',
    
            // Event details
            'event_image' => $row['image'] ?? '',
            'event_name' => $row['event_name'] ?? '',
            'event_date_time' => $row['event_date_time'] ?? '',
            'venue_address' => $row['venue_address'] ?? '',
            'event_link' => $row['event_link'] ?? '',
            'speakers' => $row['speakers'] ?? '',
    
            // Kudos details
            'card_id' => $card_id,
            'card_title' => $card_title,
            'card_description' => $card_description,
            'hashtags' => $hashtags,
            'image_by_user' => $row['file_image'] ?? '',
            
            // services data
            'service_title' => $row['need_help'] ?? '',
            'service_location'=>$row['location'] ??'',
    
            // Table name
            'post_type' => $type,
    
            // User details
            'name' => $row['name'] ?? '',
            'email' => $row['email'] ?? '',
            'profile' => $row['profile'] ?? '',
            'bannerImage' => $row['bannerImage'] ?? '',
    
            // Polls-related details
            'pollQuestion' => $type === 'polls' ? $row['ask_a_question'] : '',
            'choices' => $type === 'polls' ? $choices : [],
            'poll_duration' => $type === 'polls' ? ($row['poll_duration'] ?? 0) : 0, // Default to 0
            'PollsRespondCount' => $type === 'polls' ? ($totalRespondCount ?: 0) : 0, // Default to 0
            'user_respond' => $user_respond,
    
            // Authenticated user's vote (remains unchanged by other users' actions)
            'user_vote' => $user_vote, // User's own vote status: 0 (no vote), 1 (upvoted), or 2 (downvoted)
        ];
    }
    
    private function filterPostData($post)
    {
        $choices = $post['choices'] ?? [];
        \Log::info('Filter Post Data:', $post);
        
        return [
            'id' => $post['id'] ?? '',
            'title' => $post['title'] ?? '',
            'user_id' => $post['user_id'] ?? '',
            'description' => $post['description'] ?? '',
            'files' => $post['files'] ?? [],
            'card_image'=> $post['card_image']??'',
           'upvote' => $post['upvote'] ?? 0,
           'downvote' => $post['downvote'] ?? 0,
           'share' => $post['share'] ?? 0,
           'repost' => $post['repost'] ?? 0,
           'created_at' => $post['created_at'] ?? '',
           'updated_at' => $post['updated_at'] ?? '',
           
           //event variables
           'event_image' => $post['event_image'] ?? '',
           'event_name' => $post['event_name'] ?? '',
           'event_date_time' => $post['event_date_time'] ?? '',
           'venue_address' => $post['venue_address'] ?? '',
           'event_link' => $post['event_link'] ?? '',
           'speakers' => $post['speakers'] ?? '',
      

          //kudos and card variables
          'card_id' => $post['card_id'] ?? 0,
          'card_title' => $post['card_title'] ?? '',
          'card_description' => $post['card_description'] ?? '',
          'hashtags' => $post['hashtags'] ?? '',
          'image_by_user' => $post['image_by_user'] ?? '',      
          'following_members' => $this->getFollowingNames($post['user_id']),

         // Poll details 
         'pollQuestion' => $post['post_type'] === 'polls' ? $post['pollQuestion'] : '',
         'choices' => $post['post_type'] === 'polls' ? $choices : [],
        'poll_duration' => $post['post_type'] === 'polls' ? ($post['poll_duration'] ?? 0) : 0, // Default to 0
        'PollsRespondCount' => $post['post_type'] === 'polls' ? ($post['PollsRespondCount'] ?? 0) : 0, // Default to 0
         'user_respond' => $post['user_respond'] ?? 0,
         
         //services data 
          'service_title' => $post['service_title'] ?? '',
          'service_location' => $post['service_location'] ?? '',

          //user details
          'post_type' => $post['post_type'] ?? '',

          //uservote
          'user_vote' => $post['user_vote']??'',
    ];
}
    
}