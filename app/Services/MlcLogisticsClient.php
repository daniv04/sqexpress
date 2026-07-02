<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class MlcLogisticsClient
{
    private const TOKEN_CACHE_KEY = 'mlclogistics:token';

    public function http(): PendingRequest
    {
        return Http::baseUrl(config('services.mlclogistics.base_url'))
            ->withHeaders([
                'apiKey' => config('services.mlclogistics.api_key'),
                'Authorization' => 'Bearer ' . $this->token(),
            ]);
    }

    public function forgetToken(): void
    {
        Cache::forget(self::TOKEN_CACHE_KEY);
    }

    private function token(): string
    {
        return Cache::get(self::TOKEN_CACHE_KEY) ?? $this->login();
    }

    private function login(): string
    {
        $response = Http::baseUrl(config('services.mlclogistics.base_url'))
            ->withHeaders(['apiKey' => config('services.mlclogistics.api_key')])
            ->post('/users/login', [
                'email' => config('services.mlclogistics.email'),
                'password' => config('services.mlclogistics.password'),
            ])
            ->throw();

        $token = $response->json('token');
        $expiresAt = Carbon::parse($response->json('expiracion'))->subMinute();

        Cache::put(self::TOKEN_CACHE_KEY, $token, $expiresAt);

        return $token;
    }
}
