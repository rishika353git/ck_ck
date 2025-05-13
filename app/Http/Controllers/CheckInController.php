<?php

namespace App\Http\Controllers;

use App\Models\CheckIn;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CheckInController extends Controller
{
    public function checkin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'court' => 'required',
            'sub_court' => 'required',
            'visit_time' => 'required',
            'expiry_time' => 'required',
            'reason_to_visit' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'responseCode' => '400',
                'responseMessage' => $validator->errors()->first(),
                'responseType' => 'fail',
            ], 200);
        }

        $currentTime = Carbon::now()->format('Y-m-d H:i:s');
        $userId = Auth::id();
        $checkIn = CheckIn::where('user_id', $userId)->latest('created_at')->first();

        if (!empty($checkIn) && $checkIn->expiry_time > $currentTime) {
            return response()->json([
                'responseCode' => '200',
                'responseMessage' => 'You are already engaged',
                'responseType' => 'fail',
            ], 200);
        }

        $data = [
            'user_id' => $userId,
            'court' => $request->court,
            'sub_court' => $request->sub_court,
            'visit_time' => $request->visit_time,
            'expiry_time' => $request->expiry_time,
            'reason_to_visit' => $request->reason_to_visit,
        ];

        DB::beginTransaction();
        try {
            // Mark the previous check-in as completed
            if ($checkIn) {
                $checkIn->status = 1;
                $checkIn->save();
            }

            // Create a new check-in entry
            $newCheckIn = CheckIn::create($data);
            DB::commit();

            return response()->json([
                'responseCode' => '200',
                'responseMessage' => 'Check-In Successfully',
                'responseType' => 'success',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'responseCode' => '500',
                'responseMessage' => 'Internal Server Error',
                'responseType' => 'fail',
            ], 500);
        }
    }

    public function history(Request $request)
{
    try {
        $userId = Auth::id();
        
        // Default to 10 items per page if not provided in the request
        $perPage = $request->input('per_page', 10);

        // Query to retrieve check-in history with pagination
        $checkInQuery = DB::table('checkin')
            ->join('court', 'checkin.court', '=', 'court.id')
            ->join('sub_court', 'checkin.sub_court', '=', 'sub_court.id')
            ->select(
                'court.name as court_name',
                'sub_court.name as sub_court_name',
                'checkin.visit_time',
                'checkin.expiry_time',
                'checkin.reason_to_visit',
                'checkin.status'
            )
            ->where('checkin.user_id', $userId);

        $paginatedData = $checkInQuery->paginate($perPage);

        // Prepare the response
        $response = [
            'responseCode' => '200',
            'responseMessage' => $paginatedData->total() . ' entries found',
            'responseType' => 'success',
            'data' => [
                'checkin_data' => $paginatedData->items(),
                'pagination' => [
                    'total' => $paginatedData->total(),
                    'per_page' => $paginatedData->perPage(),
                    'current_page' => $paginatedData->currentPage(),
                    'last_page' => $paginatedData->lastPage(),
                    'next_page_url' => $paginatedData->nextPageUrl() ?? '',
                    'prev_page_url' => $paginatedData->previousPageUrl() ?? '',
                ],
            ],
        ];

        return response()->json($response, 200);

    } catch (\Exception $e) {
        // Handle any errors that may occur
        return response()->json([
            'responseCode' => '401',
            'responseMessage' => 'An error occurred: ' . $e->getMessage(),
            'responseType' => 'error'
        ], 200);
    }
}


    public function update()
    {
        $userId = Auth::id();
        $checkIn = CheckIn::where('user_id', $userId)->latest('created_at')->first();

        if (empty($checkIn)) {
            return response()->json([
                'responseCode' => '200',
                'responseMessage' => 'Currently, you are free.',
                'responseType' => 'success',
            ], 200);
        }

        $currentTime = Carbon::now()->format('Y-m-d H:i:s');

        DB::beginTransaction();
        try {
            $checkIn->expiry_time = $currentTime;
            $checkIn->status = 1;
            $checkIn->save();
            DB::commit();

            return response()->json([
                'responseCode' => '200',
                'responseMessage' => 'Currently, you are free.',
                'responseType' => 'success',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'responseCode' => '500',
                'responseMessage' => 'Internal Server Error',
                'responseType' => 'fail',
            ], 500);
        }
    }
}
