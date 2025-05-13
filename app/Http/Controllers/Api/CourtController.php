<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Court;
use App\Models\SubCourt;
use App\Models\CheckIn;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;

class CourtController extends Controller
{

    public function court(Request $request)
    {
        try {
            // Get the search input or set it to an empty string if not provided
            $search = $request->input('search', '');
    
            // Search for Courts, selecting only id and name
            $courts = Court::select('id', 'name')
                           ->where('name', 'LIKE', "%$search%")
                           ->where('status', 1);
    
            $perPage = $request->perpage ?? 15; // Number of items per page
            $paginatedData = $courts->paginate($perPage);
    
            // Prepare the response
            $response = [
                'responseCode' => '200',
                'responseMessage' => 'success',
                'data' => [
                    'courtdata' => $paginatedData->items(),
                    'pagination' => [
                        'total' => $paginatedData->total(),
                        'per_page' => $paginatedData->perPage(),
                        'current_page' => $paginatedData->currentPage(),
                        'last_page' => $paginatedData->lastPage(),
                    ],
                ],
            ];
    
            return response()->json($response, 200);
    
        } catch (\Exception $e) {
            // Handle any errors that may occur
            return response()->json([
                'responseCode' => 401,
                'responseMessage' => 'An error occurred: ' . $e->getMessage(),
                'responseType' => 'error'
            ], 200);
        }
    }

    // public function court(Request $request)
    // {
    //     try {
    //         if ($request->isMethod('post')) {
    //             // Handle storing new court data
    //             $validator = Validator::make($request->all(), [
    //                 'name' => 'required|string|max:255',
    //                 'status' => 'required|boolean',
    //             ]);
    
    //             if ($validator->fails()) {
    //                 return response()->json([
    //                     'responseCode' => '400',
    //                     'responseMessage' => 'Validation failed: ' . $validator->errors()->first(),
    //                     'responseType' => 'error'
    //                 ], 400);
    //             }
    
    //             $court = Court::create([
    //                 'name' => $request->input('name'),
    //                 'status' => $request->input('status'),
    //             ]);
    
    //             return response()->json([
    //                 'responseCode' => '201',
    //                 'responseMessage' => 'Court created successfully',
    //                 'data' => $court
    //             ], 201);
    //         } else {
    //             // Handle retrieving court data
    //             $search = $request->input('search', '');
    //             $courts = Court::select('id', 'name')
    //                            ->where('name', 'LIKE', "%$search%")
    //                            ->where('status', 1);
    
    //             $perPage = $request->input('perpage', 15);
    //             $paginatedData = $courts->paginate($perPage);
    
    //             $response = [
    //                 'responseCode' => '200',
    //                 'responseMessage' => 'success',
    //                 'data' => [
    //                     'courtdata' => $paginatedData->items(),
    //                     'pagination' => [
    //                         'total' => $paginatedData->total(),
    //                         'per_page' => $paginatedData->perPage(),
    //                         'current_page' => $paginatedData->currentPage(),
    //                         'last_page' => $paginatedData->lastPage(),
    //                     ],
    //                 ],
    //             ];
    
    //             return response()->json($response, 200);
    //         }
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'responseCode' => '500',
    //             'responseMessage' => 'An error occurred: ' . $e->getMessage(),
    //             'responseType' => 'error'
    //         ], 500);
    //     }
    // }
    


public function subcourt(Request $request)
{
    // Retrieve court_id from the query parameters
    $court_id = $request->query('court_id');
    
    if (is_null($court_id)) {
        return response()->json([
            'responseCode' => '400',
            'responseMessage' => 'Court ID is required',
            'responseType' => 'fail'
        ], 200);
    }

    // Get the search input or set it to an empty string if not provided
    $search = $request->input('search', '');

    // Search for Sub Courts based on court_id and search input
    $subCourtsQuery = SubCourt::select('id', 'name')
                              ->where('court_id', $court_id)
                              ->where('name', 'LIKE', "%$search%");

    $perPage = $request->input('perpage', 15); // Number of items per page, default is 15
    $paginatedData = $subCourtsQuery->paginate($perPage);

    if ($paginatedData->isNotEmpty()) {
        $response = [
            'responseCode' => '200',
            'responseMessage' => $paginatedData->total() . ' Sub Courts found',
            'responseType' => 'success',
            'subCourtData' => [
                'court_id' => (int) $court_id, // Cast court_id to integer
                'subCourts' => $paginatedData->items(),
                'pagination' => [
                    'total' => $paginatedData->total(),
                    'per_page' => $paginatedData->perPage(),
                    'current_page' => $paginatedData->currentPage(),
                    'last_page' => $paginatedData->lastPage(),
                ],
            ],
        ];
    } else {
        $response = [
            'responseCode' => '200',
            'responseMessage' => 'No Sub Courts found',
            'responseType' => 'success',
            'subCourtData' => [
                'court_id' => (int) $court_id, // Cast court_id to integer
                'subCourts' => [],
                'pagination' => [
                    'total' => 0,
                    'per_page' => $perPage,
                    'current_page' => 1,
                    'last_page' => 1,
                ],
            ],
        ];
    }

    return response()->json($response, 200);
}


    public function availableUser(Request $request)
        {
            $validator = Validator::make($request->all(), [
                'court_id' => 'required',
                'subcourt_id' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'responseCode' => '401',
                    'responseMessage' => $validator->errors()->first(),
                    'responseType' => 'fail'],
                    200);
            }else{
               $court_id = $request->court_id;
                $subcourt_id = $request->subcourt_id;
                $time = Carbon::now()->format('Y-m-d H:i:s');
                $currentTime = Carbon::now();

                $currentTime->addWeek(); // Add one week to the current time
                $oneWeekLater = $currentTime->format('Y-m-d H:i:s');

                $data = CheckIn::where('court', $court_id)
                              -> where('sub_court', $subcourt_id)
                               ->where('visit_time', '>' , $time )
                               ->where('expiry_time', '<', $oneWeekLater )
                               ->get();

                if (count($data) > 0) {
                    $response = [
                        'responseCode' => '200',
                        'responseMessage' => count($data) . ' User found',
                        'responseType' => 'success',
                        'currentime' => $currentTime->format('Y-m-d H:i:s'),
                        'data' => $data,
                    ];
                } else {
                    $response = [
                        'responseCode' => '200',
                        'responseMessage' => count($data) . ' User found',
                        'responseType' => 'success',
                        'currentime' => $currentTime->format('Y-m-d H:i:s'),
                        'data' => $data,

                    ];
                }
                return response()->json($response, 200);
            }
        }

}
