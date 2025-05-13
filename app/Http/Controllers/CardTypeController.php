<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CardTypeController extends Controller
{
    public function store(Request $request)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'card_title' => 'required|string|max:255',
            'card_description' => 'required|string',
            'card_image' => 'required|string|max:255',
        ]);

        // Predefined card types
        $cardtypes = [
            [
                "id" => 1,
                "card_title" => "Thank you",
                "card_description" => "@Thankyou",
                "card_image" =>asset( "storage/upload/card1.png"),
                "created_at" => "2024-07-31T07:47:12.000000Z",
                "updated_at" => "2024-07-31T07:47:12.000000Z"
            ],
            [
                "id" => 2,
                "card_title" => "Team Player",
                "card_description" => "@Team Player",
                "card_image" => asset("storage/upload/card2.png"),
                "created_at" => "2024-08-02T05:23:24.000000Z",
                "updated_at" => "2024-08-02T05:23:24.000000Z"
            ],
            [
                "id" => 3,
                "card_title" => "Great Job",
                "card_description" => "@Great Job",
                "card_image" => asset("storage/upload/card3.png"),
                "created_at" => "2024-08-03T05:55:11.000000Z",
                "updated_at" => "2024-08-03T05:55:11.000000Z"
            ],
            [
                "id" => 4,
                "card_title" => "Making Work fun",
                "card_description" => "@Making work fun",
                "card_image" => asset("storage/upload/card4.png"),
                "created_at" => "2024-08-03T10:39:45.000000Z",
                "updated_at" => "2024-08-03T10:39:45.000000Z"
            ],
            [
                "id" => 5,
                "card_title" => "Amazing Mentor",
                "card_description" => "@Amazing Mentor",
                "card_image" => asset("storage/upload/card5.png"),
                "created_at" => "2024-08-03T10:39:45.000000Z",
                "updated_at" => "2024-08-03T10:39:45.000000Z"
            ],
            [
                "id" => 6,
                "card_title" => "Outside the Box Thinker",
                "card_description" => "@Outside the Box Thinker",
                "card_image" =>asset("storage/upload/card6.png"),
                "created_at" => "2024-08-03T10:41:17.000000Z",
                "updated_at" => "2024-08-03T10:41:17.000000Z"
            ],
        ];

        // Return a response
        return response()->json([
            'success' => true,
            'data' => $validatedData,
            'cardtype' => $cardtypes
        ], 201);
    }

    public function getCardDetails(Request $request)
    {
        $request->validate([
            'card_type_id' => 'required|integer|exists:card_types,id', // Adjust table name if needed
        ]);

        $cardId = $request->input('card_type_id');
        $card = CardType::find($cardId);

        if (!$card) {
            return response()->json([
                'responseCode' => '404',
                'responseType' => 'error',
                'message' => 'Card not found'
            ], 404);
        }

        return response()->json([
            'responseCode' => '200',
            'responseType' => 'success',
            'card' => $card
        ], 200);
    }

    public function getcardtypes()
    {
        // Predefined card types
        $cardtypes = [
            [
                "id" => 1,
                "card_title" => "Thank you",
                "card_description" => "@Thankyou",
                "card_image" => asset("storage/upload/card1.png"),
                "created_at" => "2024-07-31T07:47:12.000000Z",
                "updated_at" => "2024-07-31T07:47:12.000000Z"
            ],
            [
                "id" => 2,
                "card_title" => "Team Player",
                "card_description" => "@Team Player",
                "card_image" => asset("storage/upload/card2.png"),
                "created_at" => "2024-08-02T05:23:24.000000Z",
                "updated_at" => "2024-08-02T05:23:24.000000Z"
            ],
            [
                "id" => 3,
                "card_title" => "Great Job",
                "card_description" => "@Great Job",
                "card_image" => asset("storage/upload/card3.png"),
                "created_at" => "2024-08-03T05:55:11.000000Z",
                "updated_at" => "2024-08-03T05:55:11.000000Z"
            ],
            [
                "id" => 4,
                "card_title" => "Making Work fun",
                "card_description" => "@Making work fun",
                "card_image" =>asset( "storage/upload/card4.png"),
                "created_at" => "2024-08-03T10:39:45.000000Z",
                "updated_at" => "2024-08-03T10:39:45.000000Z"
            ],
            [
                "id" => 5,
                "card_title" => "Amazing Mentor",
                "card_description" => "@Amazing Mentor",
                "card_image" => asset("storage/upload/card5.png"),
                "created_at" => "2024-08-03T10:39:45.000000Z",
                "updated_at" => "2024-08-03T10:39:45.000000Z"
            ],
            [
                "id" => 6,
                "card_title" => "Outside the Box Thinker",
                "card_description" => "@Outside the Box Thinker",
                "card_image" => asset("storage/upload/card6.png"),
                "created_at" => "2024-08-03T10:41:17.000000Z",
                "updated_at" => "2024-08-03T10:41:17.000000Z"
            ],
        ];

        return response()->json([
            'responseCode' => '200',
            'responseType' => 'success',
            'cardtype' => $cardtypes
        ], 200);
    }
}
