<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TopicnGroup;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class TopicsandGroupController extends Controller
{
    public function index(request $request){
        //pagination
       // Retrieve paginated records
       $perPage = $request->input('per_page', 10); 
       // Current page number
       $currentPage = $request->input('page', 1); 

       $topicGroups = TopicnGroup::paginate($perPage);

       //response structure
       $response = [
           'responseCode' => '200',
           'responseType' => 'success',
           'data' => $topicGroups->items(), 
           'pagination' => [
           'total' => $topicGroups->total(),
           'per_page' => $topicGroups->perPage(),
           'current_page' => $topicGroups->currentPage(),
           'last_page' => $topicGroups->lastPage(),
           ],
       ];

       return response()->json($response); 
   }

     // Store method to create a new topic group
    public function store(Request $request)
    {
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'membersCount' => 'nullable|integer',
            'bgColor' => 'nullable|string|max:7', 
            'status' => 'nullable|integer',
        ]);

        // Return validation errors if any
        if ($validator->fails()) {
            return response()->json([
                'responseCode' => '400',
                'responseType' => 'error',
                'errors' => $validator->errors(),
            ], 400);
        }

         // Handle image file upload if it exists
    $imagePath = null;
    if ($request->hasFile('image')) {
        $imagePath = $request->file('image')->store('uploads', 'public'); // Save image in 'public/uploads'
    }
        // Create a new topic group
        $topicGroup = TopicnGroup::create([
            'title' => $request->title,
            'description' => $request->description,
            'image' => $imagePath,
            'membersCount' => $request->membersCount,
            'bgColor' => $request->bgColor,
            'status' => $request->status,
        ]);

        // Response structure
        return response()->json([
            'responseCode' => '200',
            'responseType' => 'success',
            'data' => $topicGroup,
        ], 201);
    }
}