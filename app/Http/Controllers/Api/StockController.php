<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StockMarketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function chart(Request $request, StockMarketService $stockMarketService): JsonResponse
    {
        $data = $request->validate([
            'symbol' => ['required', 'string', 'max:10'],
        ]);

        $chart = $stockMarketService->chart($data['symbol']);

        return response()->json([
            'ok' => true,
            'data' => $chart,
        ]);
    }
}
