<?php

namespace App\Services;

class ChatIntentService
{
    public function detectMode(string $message): string
    {
        $original = trim($message);
        $text = mb_strtolower($original);

        $strongKeywords = [
            'saham',
            'stock',
            'ticker',
            'harga saham',
            'analisis saham',
            'candlestick',
            'support',
            'resistance',
            'breakout',
            'dividen',
            'market cap',
            'earnings',
            'ihsg',
            'idx',
            'bei',
            'nasdaq',
            'nyse',
            'dow jones',
        ];

        $companyKeywords = [
            'bbca', 'bbri', 'bmri', 'asii', 'tlkm', 'bbni',
            'aapl', 'tsla', 'nvda', 'msft', 'amzn', 'meta',
        ];

        $score = 0;

        foreach ($strongKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                $score += 2;
            }
        }

        foreach ($companyKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                $score += 2;
            }
        }

        if (preg_match('/\b[A-Z]{4,5}\b/', $original)) {
            $score += 1;
        }

        return $score >= 2 ? 'stock' : 'general';
    }
}
