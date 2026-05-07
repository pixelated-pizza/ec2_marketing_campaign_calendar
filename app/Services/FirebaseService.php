<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class FirebaseService
{
    protected string $apiKey = 'AIzaSyBzXaWzsWGmt29UTNYUwXFJRLZfNKwJcKg';

    /**
     * Verify Firebase ID token via REST API
     *
     * @param string $idToken
     * @return array|null
     */
    public function verifyIdToken(string $idToken): ?array
    {
        try {
            $response = Http::post(
                "https://identitytoolkit.googleapis.com/v1/accounts:lookup?key={$this->apiKey}",
                ['idToken' => $idToken]
            );

            $data = $response->json();

            // Check if user exists
            if (!isset($data['users'][0])) {
                Log::warning('Firebase token invalid: no user returned', ['token' => $idToken]);
                return null;
            }

            $user = $data['users'][0];

            return [
                'uid'   => $user['localId'],
                'email' => $user['email'] ?? null,
                'name'  => $user['displayName'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::error('Firebase token verification failed: ' . $e->getMessage(), ['token' => $idToken]);
            return null;
        }
    }
}
