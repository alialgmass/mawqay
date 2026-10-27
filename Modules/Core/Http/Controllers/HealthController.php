<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * The address an uptime monitor watches. It says only whether the app and its database answer, nothing else.
 */
class HealthController
{
    public function __invoke(): JsonResponse
    {
        try {
            DB::select('select 1');
        } catch (Throwable) {
            return response()->json(['status' => 'down'], 503);
        }

        return response()->json(['status' => 'ok']);
    }
}
