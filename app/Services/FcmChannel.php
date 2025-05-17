<?php

namespace App\Services;

use GuzzleHttp\Client;
use Google_Client;

class FcmChannel
{
    public function send(string $deviceToken, string $title, string $body)
    {
        $client = new \GuzzleHttp\Client();

        $response = $client->post(
            'https://fcm.googleapis.com/v1/projects/' . env('FIREBASE_PROJECT_ID') . '/messages:send',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
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

        // Return response lengkap
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


    private function getAccessToken()
    {
        $credentialsPath = storage_path('app/firebase-service-account.json'); // Path to your service account file

        $client = new Google_Client();
        $client->setAuthConfig($credentialsPath);
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

        $token = $client->fetchAccessTokenWithAssertion();
        return $token['access_token'];
    }
}
