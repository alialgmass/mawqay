<?php

namespace Modules\Payments\Support;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Payments\Exceptions\OnlinePaymentFailed;

/**
 * Talks to Fawaterk: OAuth token, hosted-checkout transactions and webhook signatures.
 */
class FawaterkClient
{
    private const TOKEN_CACHE_KEY = 'payments.fawaterk.token';

    public function isConfigured(): bool
    {
        return filled(config('payments.fawaterk.client_id'))
            && filled(config('payments.fawaterk.client_secret'))
            && filled(config('payments.fawaterk.hash_key'));
    }

    /**
     * Creates a hosted-checkout transaction and returns its intent key and checkout URL.
     *
     * @param  array<string, mixed>  $body
     * @return array{intent_key: string, url: string}
     *
     * @throws OnlinePaymentFailed
     */
    public function createTransaction(array $body): array
    {
        try {
            $response = $this->post('/api/v3/createTransaction', $body);
        } catch (RequestException $exception) {
            throw new OnlinePaymentFailed(__('payments::online.errors.unavailable'), previous: $exception);
        }

        $data = $response['data'] ?? [];

        if (($response['status'] ?? null) !== 'success' || blank($data['intent_key'] ?? null) || blank($data['url'] ?? null)) {
            throw new OnlinePaymentFailed(__('payments::online.errors.unavailable'));
        }

        return ['intent_key' => (string) $data['intent_key'], 'url' => (string) $data['url']];
    }

    /**
     * Whether a webhook's hash matches HMAC-SHA256 of the string-to-sign under the vendor key.
     */
    public function isValidSignature(string $stringToSign, ?string $hash): bool
    {
        $key = (string) config('payments.fawaterk.hash_key');

        if ($key === '' || blank($hash)) {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $stringToSign, $key), strtolower((string) $hash));
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function post(string $path, array $body, bool $retry = true): array
    {
        $response = Http::baseUrl((string) config('payments.fawaterk.base_url'))
            ->withToken($this->token())
            ->acceptJson()
            ->timeout(20)
            ->post($path, $body);

        if ($response->status() === 401 && $retry) {
            Cache::forget(self::TOKEN_CACHE_KEY);

            return $this->post($path, $body, false);
        }

        return $response->throw()->json() ?? [];
    }

    private function token(): string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, now()->addDays(7), function (): string {
            $response = Http::baseUrl((string) config('payments.fawaterk.base_url'))
                ->acceptJson()
                ->timeout(20)
                ->asForm()
                ->post('/oauth/token', [
                    'grant_type' => 'client_credentials',
                    'client_id' => config('payments.fawaterk.client_id'),
                    'client_secret' => config('payments.fawaterk.client_secret'),
                ])
                ->throw();

            return (string) $response->json('access_token');
        });
    }
}
