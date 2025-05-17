<?php

namespace App\Http\Controllers;

use App\Services\FcmChannel;
use Illuminate\Http\Request;

class NotifController 
{
    protected $firebase;

    public function __construct(FcmChannel $firebase)
    {
        $this->firebase = $firebase;
    }

    public function notifyUser(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->fcm_token) {
            return response()->json([
                'success' => false,
                'message' => 'FCM token tidak tersedia.',
            ], 400);
        }

        $token = $user->fcm_token;

        $result = $this->firebase->send(
            $token,
            "TES",
            "Anjay Masuk BANG BANGGA",
        );

        return response()->json([
            'success' => true,
            'firebase_response' => $result
        ]);
    }
}
