<?php

namespace Modules\Payments\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Payments\Actions\SettleOnlinePayment;
use Modules\Payments\Support\FawaterkClient;

/**
 * Receives Fawaterk's server-to-server notifications. Every call is signature-checked
 * (HMAC-SHA256 with the vendor key) before anything changes; the Customer's browser
 * redirect is never trusted to mark an Order paid.
 */
class FawaterkWebhookController
{
    public function __construct(
        private readonly FawaterkClient $client,
        private readonly SettleOnlinePayment $settle,
    ) {}

    public function paid(Request $request): JsonResponse
    {
        $key = (string) $request->input('transaction_key');

        if ($key === '' || ! $this->client->isValidSignature($this->stringToSign($request, $key), $request->input('transactionHashKey'))) {
            return response()->json(['message' => 'invalid signature'], 403);
        }

        if ($request->input('status') === 'paid') {
            $this->settle->paid($key, (string) $request->input('transaction_id'), $request->input('paidAmount'), $request->all());
        }

        return response()->json(['ok' => true]);
    }

    public function failed(Request $request): JsonResponse
    {
        $key = (string) $request->input('transaction_key');

        if ($key === '' || ! $this->client->isValidSignature($this->stringToSign($request, $key), $request->input('hashKey'))) {
            return response()->json(['message' => 'invalid signature'], 403);
        }

        $this->settle->failed($key, $request->input('errorMessage'), $request->all());

        return response()->json(['ok' => true]);
    }

    private function stringToSign(Request $request, string $key): string
    {
        return 'TransactionId='.$request->input('transaction_id').'&TransactionKey='.$key.'&PaymentMethod='.$request->input('payment_method');
    }
}
