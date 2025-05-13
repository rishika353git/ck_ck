<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ForumHiringPost;
use App\Models\JobApplication;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ForumHiringPostController extends Controller
{
    protected $notificationController;

    // Inject NotificationController
    public function __construct(NotificationController $notificationController)
    {
        $this->notificationController = $notificationController;
    }

 public function index(Request $request)
{
    try {
        $userId = Auth::id(); // Assuming the user is authenticated
        $perPage = $request->get('perpage', 15);
        $currentPage = $request->get('page', 1);

        $posts = DB::table('forum_hiring_post')
            ->join('users', 'forum_hiring_post.user_id', '=', 'users.id')
            ->leftJoin('job_applications', 'forum_hiring_post.id', '=', 'job_applications.job_id')
            ->select(
                'forum_hiring_post.id',
                'users.id as user_id',
                'users.name',
                'users.profile',
                'forum_hiring_post.created_at',
                'forum_hiring_post.entity_name',
                'forum_hiring_post.workplace',
                'forum_hiring_post.job_location',
                'forum_hiring_post.job_description',
                'forum_hiring_post.job_type',
                'forum_hiring_post.job_title',
                DB::raw('COUNT(job_applications.id) as application_count')
            )
            ->groupBy(
                'forum_hiring_post.id',
                'users.id',
                'users.name',
                'users.profile',
                'forum_hiring_post.created_at',
                'forum_hiring_post.entity_name',
                'forum_hiring_post.workplace',
                'forum_hiring_post.job_location',
                'forum_hiring_post.job_description',
                'forum_hiring_post.job_type',
                'forum_hiring_post.job_title'
            )
            ->paginate($perPage, ['*'], 'page', $currentPage);

        $posts->getCollection()->transform(function ($post) use ($userId) {
            $application = DB::table('job_applications')
                ->where('job_id', $post->id)
                ->where('user_id', $userId)
                ->first();
            $post->status = $application ? 1 : 0;
            return $post;
        });

        $response = [
            'responseCode' => '200',
            'responseType' => 'success',
            'data' => $posts->items(),
            'pagination' => [
                'current_page' => $posts->currentPage(),
                'per_page' => $posts->perPage(),
                'total_posts' => $posts->total(),
                'total_pages' => $posts->lastPage(),
            ],
        ];

        return response()->json($response, 200);
    } catch (\Exception $e) {
        return response()->json([
            'responseCode' => '401',
            'responseMessage' => 'An error occurred: ' . $e->getMessage(),
            'responseType' => 'error'
        ], 200);
    }
}

 public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'job_title' => 'required',
            'entity_name' => 'required',
            'workplace' => 'required|integer',
            'job_location' => 'required',
            'job_description' => 'required',
            'job_type' => 'required|integer',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'responseCode' => '200',
                'responseMessage' => $validator->errors()->first(),
                'responseType' => 'fail'
            ], 200);
        } else {
            $userId = Auth::id();
            $data = [
                'user_id' => $userId,
                'job_title' => $request->job_title,
                'entity_name' => $request->entity_name,
                'workplace' => $request->workplace,
                'job_location' => $request->job_location,
                'job_description' => $request->job_description,
                'job_type' => $request->job_type,
            ];
            
            DB::beginTransaction();
            try {
                $post = ForumHiringPost::create($data);
                
                // Fetch tokens from the database or another source
                $tokens = ['dq8grBH1Tp-hyEJsY_Jici:APA91bHJOnAEJqalv-gOytrZ7ulOh0Dcuex6rwsWrLuVDgvabvH6B2-0TNZ0GRBVfV9eC7t8UZ3BBbFHsQdKTGPcWezRAOSLYOW03o8tA8-Dcio2Ic_i8arMZyY--ndpgfTTVjn4Cec6']; // Replace with actual tokens
                
                foreach ($tokens as $token) {
                    $notificationRequest = new Request([
                        'token' => $token,
                        'title' => 'New Job Post Created',
                        'body' => 'A new job post has been added.',
                        'data' => ['job_id' => $post->id]
                    ]);

                    $this->notificationController->sendPushNotification($notificationRequest);
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'responseCode' => '401',
                    'responseMessage' => 'Internal Server Error: ' . $e->getMessage(),
                    'responseType' => 'fail'
                ], 200);
            }
            
            return response()->json([
                'responseCode' => '200',
                'responseMessage' => 'Post added successfully',
                'responseType' => 'success',
                'data' => $post,
            ], 200);
        }
    }

    public function applyForJob(Request $request)
    {
        try {
            $userId = Auth::id(); // Assuming the user is authenticated
            if (is_null($userId)) {
                return response()->json([
                    'responseCode' => '401',
                    'responseMessage' => 'User is not authenticated.',
                    'responseType' => 'fail'
                ], 200);
            }
            $jobId = $request->job_id;
            // Check if job exists
            $job = ForumHiringPost::find($jobId);
            if (!$job) {
                return response()->json([
                    'responseCode' => '404',
                    'responseMessage' => 'Job not found.',
                    'responseType' => 'fail'
                ], 200);
            }
            // Check if user has already applied
            $existingApplication = DB::table('job_applications')
                ->where('job_id', $jobId)
                ->where('user_id', $userId)
                ->first();
            if ($existingApplication) {
                return response()->json([
                    'responseCode' => '200',
                    'responseMessage' => 'User has already applied for this job.',
                    'responseType' => 'fail'
                ], 200);
            }
            // Create a new application
            DB::table('job_applications')->insert([
                'job_id' => $jobId,
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return response()->json([
                'responseCode' => '200',
                'responseMessage' => 'Job application successful.',
                'responseType' => 'success'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'responseCode' => '500',
                'responseMessage' => 'An error occurred: ' . $e->getMessage(),
                'responseType' => 'error'
            ], 200);
        }
    }

    public function search(Request $request)
    {
        try {
            // Define the base query
            $query = DB::table('forum_hiring_post')
                ->select(
                    'forum_hiring_post.id',
                    'forum_hiring_post.user_id',
                    'forum_hiring_post.created_at',
                    'forum_hiring_post.entity_name',
                    'forum_hiring_post.workplace',
                    'forum_hiring_post.job_location',
                    'forum_hiring_post.job_description',
                    'forum_hiring_post.job_type',
                    'forum_hiring_post.job_title'
                );
    
            if ($request->has('job_title') && !empty($request->job_title)) { 
                $query->where('forum_hiring_post.job_title', '=', $request->job_title);
            } else {
                // Return an empty result if no job_title is provided
                return response()->json([
                    'responseCode' => '400',
                    'responseMessage' => 'Job title is required.',
                    'responseType' => 'fail'
                ], 200);
            }
    
            $perPage = $request->get('perpage', 15);
            $currentPage = $request->get('page', 1);
    
            // Paginate results
            $posts = $query->paginate($perPage, ['*'], 'page', $currentPage);
    
            $response = [
                'responseCode' => '200',
                'responseType' => 'success',
                'data' => $posts->items(),
                'pagination' => [
                    'total' => $posts->total(),
                    'per_page' => $posts->perPage(),
                    'current_page' => $posts->currentPage(),
                    'last_page' => $posts->lastPage(),
                ],
            ];
            return response()->json($response, 200);
        } catch (\Exception $e) {
            return response()->json([
                'responseCode' => '500',
                'responseMessage' => 'An error occurred: ' . $e->getMessage(),
                'responseType' => 'error'
            ], 200);
        }
    }
}
