<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class StockMarketService
{
    public function chart(string $symbol): array
    {
        $response = Http::acceptJson()
            ->timeout(30)
            ->get('https://www.alphavantage.co/query', [
                'function' => 'TIME_SERIES_DAILY',
                'symbol' => strtoupper($symbol),
                'apikey' => config('services.alphavantage.key'),
                'outputsize' => 'compact',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Gagal mengambil data saham.');
        }

        $json = $response->json();

        $series = $json['Time Series (Daily)'] ?? null;

        if (! $series || ! is_array($series)) {
            return [
                'symbol' => strtoupper($symbol),
                'latest_date' => null,
                'latest_open' => null,
                'latest_high' => null,
                'latest_low' => null,
                'latest_close' => null,
                'points' => [],
                'note' => $json['Note'] ?? $json['Information'] ?? 'Data tidak tersedia',
            ];
        }

        $points = collect($series)
            ->map(function ($item, $date) {
                return [
                    'date' => $date,
                    'open' => (float) $item['1. open'],
                    'high' => (float) $item['2. high'],
                    'low' => (float) $item['3. low'],
                    'close' => (float) $item['4. close'],
                    'volume' => (int) $item['5. volume'],
                ];
            })
            ->sortBy('date')
            ->values();

        $latest = $points->last();

        return [
            'symbol' => strtoupper($symbol),
            'latest_date' => $latest['date'] ?? null,
            'latest_open' => $latest['open'] ?? null,
            'latest_high' => $latest['high'] ?? null,
            'latest_low' => $latest['low'] ?? null,
            'latest_close' => $latest['close'] ?? null,
            'points' => $points->all(),
        ];
    }

    public function buildAnalysisContext(array $chart): array
    {
        $points = collect($chart['points'] ?? []);
        $recent = $points->count() > 20 ? $points->slice(-20)->values() : $points->values();

        $closes = $recent->pluck('close')->filter()->values();

        $trend = 'sideways';

        if ($closes->count() >= 2) {
            $first = (float) $closes->first();
            $last = (float) $closes->last();

            if ($last > $first * 1.03) {
                $trend = 'uptrend';
            } elseif ($last < $first * 0.97) {
                $trend = 'downtrend';
            }
        }

        return [
            'symbol' => $chart['symbol'] ?? null,
            'latest_date' => $chart['latest_date'] ?? null,
            'latest_open' => $chart['latest_open'] ?? null,
            'latest_high' => $chart['latest_high'] ?? null,
            'latest_low' => $chart['latest_low'] ?? null,
            'latest_close' => $chart['latest_close'] ?? null,
            'trend_20d' => $trend,
            'recent_closes' => $recent->map(fn ($p) => [
                'date' => $p['date'],
                'close' => $p['close'],
            ])->all(),
        ];
    }
}
