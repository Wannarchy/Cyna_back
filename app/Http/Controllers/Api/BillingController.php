<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class BillingController extends Controller
{
    public function config(): JsonResponse
    {
        return response()->json([
            'data' => [
                'stripe_key' => config('cashier.key'),
            ],
        ]);
    }
}
