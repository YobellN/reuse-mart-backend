<?php

namespace App\Services;

use GuzzleHttp\Client;
use Google_Client;
use App\Models\User;

class FcmChannel
{
    public static function send(string $deviceToken, string $title, string $body)
    {
        $client = new Client();
        $projectId = config('services.firebase.project_id');
        $response = $client->post(
            'https://fcm.googleapis.com/v1/projects/' . $projectId . '/messages:send',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . static::getAccessToken(),
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'message' => [
                        'token' => $deviceToken,
                        'notification' => [
                            'title' => $title,
                            'body'  => $body,
                        ],
                    ],
                ],
            ]
        );

        $result = json_decode((string) $response->getBody(), true);

        return [
            'success' => true,
            'device_token' => $deviceToken,
            'message' => [
                'title' => $title,
                'body'  => $body,
            ],
            'fcm_response' => $result
        ];
    }

    public static function sendToUser(int $id_user, string $title, string $body)
    {
        $user = User::find($id_user);

        if (!$user || !$user->fcm_token) {
            return [
                'success' => false,
                'message' => 'FCM token tidak tersedia.',
                'id_user' => $id_user,
            ];
        }

        return FcmChannel::send(
            $user->fcm_token,
            $title,
            $body
        );
    }

    private static function getAccessToken()
    {
        $credentialsPath = storage_path('app/firebase-service-account.json');

        $client = new \Google_Client();
        $client->setAuthConfig($credentialsPath);
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

        $token = $client->fetchAccessTokenWithAssertion();
        return $token['access_token'];
    }
}
