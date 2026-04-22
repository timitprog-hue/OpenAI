<?php

namespace App\Services;

class StockSymbolService
{
    public function extract(string $text): ?array
    {
        $upper = strtoupper($text);

        $idxSymbols = [
            'BBCA', 'BBRI', 'BMRI', 'BBNI', 'TLKM',
            'ASII', 'PGAS', 'ANTM', 'MDKA',
        ];

        $usSymbols = [
            'AAPL', 'TSLA', 'NVDA', 'MSFT', 'AMZN',
            'META', 'GOOG', 'AMD',
        ];

        foreach ($idxSymbols as $symbol) {
            if (str_contains($upper, $symbol)) {
                return [
                    'symbol' => $symbol,
                    'market' => 'IDX',
                ];
            }
        }

        foreach ($usSymbols as $symbol) {
            if (str_contains($upper, $symbol)) {
                return [
                    'symbol' => $symbol,
                    'market' => 'US',
                ];
            }
        }

        if (preg_match('/\b[A-Z]{4,5}\b/', $upper, $matches)) {
            $candidate = $matches[0];

            if (! $this->isBlockedWord($candidate)) {
                return [
                    'symbol' => $candidate,
                    'market' => 'UNKNOWN',
                ];
            }
        }

        return null;
    }

    protected function isBlockedWord(string $value): bool
    {
        $blocked = [
            'HALO', 'CHAT', 'ROOM', 'MODE', 'SAHAM',
            'STOCK', 'TREN', 'UNTUK', 'DENGAN',
        ];

        return in_array($value, $blocked, true);
    }
}
