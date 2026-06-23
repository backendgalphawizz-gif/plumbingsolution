<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    public function isConfigured(): bool
    {
        return is_readable(config('firebase.service_account'));
    }

    public function sendToToken(string $token, string $title, string $body, array $data = []): array
    {
        if (! $this->isConfigured() || $token === '') {
            return ['error' => true, 'message' => 'FCM not configured or token missing'];
        }

        $accessToken = $this->getAccessToken();

        if (! $accessToken) {
            return ['error' => true, 'message' => 'Access token generation failed'];
        }

        $projectId = config('firebase.project_id');
        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $stringData = ['content_available' => 'true'];
        foreach ($data as $key => $value) {
            if ($value !== null && $value !== '') {
                $stringData[(string) $key] = (string) $value;
            }
        }

        $payload = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'android' => [
                    'priority' => 'HIGH',
                    'notification' => [
                        'sound' => 'default',
                    ],
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                        ],
                    ],
                ],
                'data' => $stringData,
            ],
        ];

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->post($url, $payload);

        if ($response->failed()) {
            Log::warning('FCM send failed', [
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);

            return [
                'error' => true,
                'message' => $response->json('error.message') ?? 'FCM request failed',
                'response' => $response->json(),
            ];
        }

        return $response->json() ?? ['error' => false, 'message' => 'Notification sent'];
    }

    public function getAccessToken(): ?string
    {
        return Cache::remember('firebase_fcm_access_token', 3300, function () {
            return $this->requestAccessToken();
        });
    }

    private function requestAccessToken(): ?string
    {
        $path = config('firebase.service_account');

        if (! is_readable($path)) {
            return null;
        }

        $json = json_decode(file_get_contents($path), true);

        if (! is_array($json) || empty($json['client_email']) || empty($json['private_key'])) {
            return null;
        }

        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $now = time();
        $payload = $this->base64UrlEncode(json_encode([
            'iss' => $json['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        $signatureInput = $header.'.'.$payload;
        openssl_sign($signatureInput, $signature, $json['private_key'], OPENSSL_ALGO_SHA256);
        $jwt = $signatureInput.'.'.$this->base64UrlEncode($signature);

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        return $response->json('access_token');
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
