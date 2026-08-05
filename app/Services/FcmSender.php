<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmSender
{
    public function isConfigured(): bool
    {
        $path = config('services.fcm.credentials_path');

        return (bool) config('services.fcm.project_id') && $path && is_file($path);
    }

    /**
     * @param  string[]  $tokens
     * @param  array<string, string>  $data
     */
    public function send(array $tokens, string $title, string $body, array $data = []): void
    {
        if (! $this->isConfigured() || $tokens === []) {
            return;
        }

        $accessToken = $this->accessToken();
        if (! $accessToken) {
            return;
        }

        $projectId = config('services.fcm.project_id');
        $endpoint = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        foreach ($tokens as $token) {
            try {
                Http::withToken($accessToken)->post($endpoint, [
                    'message' => [
                        'token' => $token,
                        'notification' => ['title' => $title, 'body' => $body],
                        'data' => array_map('strval', $data),
                        'android' => ['priority' => 'high'],
                    ],
                ])->throw();
            } catch (\Throwable $e) {
                Log::warning('No se pudo enviar push FCM.', [
                    'token' => substr($token, 0, 12).'…',
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function accessToken(): ?string
    {
        return Cache::remember('fcm_access_token', 3000, function () {
            $path = config('services.fcm.credentials_path');
            $credentials = json_decode((string) file_get_contents($path), true);
            if (! is_array($credentials) || ! isset($credentials['private_key'], $credentials['client_email'])) {
                return null;
            }

            $now = time();
            $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claims = $this->base64UrlEncode(json_encode([
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3300,
            ]));

            $signature = '';
            $signed = openssl_sign(
                "{$header}.{$claims}", $signature,
                $credentials['private_key'], OPENSSL_ALGO_SHA256
            );
            if (! $signed) {
                return null;
            }

            $jwt = $header.'.'.$claims.'.'.$this->base64UrlEncode($signature);

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            return $response->successful() ? $response->json('access_token') : null;
        });
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
