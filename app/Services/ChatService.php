<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\ChatRoom;
use Illuminate\Support\Str;

class ChatService
{
    public function __construct(
        protected ChatIntentService $intentService,
        protected OpenAIService $openAIService,
        protected StockSymbolService $stockSymbolService,
        protected StockMarketService $stockMarketService,
    ) {}

    public function sendUserMessage(ChatRoom $room, string $content): array
    {
        $mode = $this->intentService->detectMode($content);

        $userMessage = ChatMessage::create([
            'chat_room_id' => $room->id,
            'role' => 'user',
            'mode' => $mode,
            'content' => $content,
            'sent_at' => now(),
        ]);

        $userMessageCount = $room->messages()->where('role', 'user')->count();

        if ($userMessageCount === 1 && $room->title === 'Chat Baru') {
            $room->update([
                'title' => Str::limit($content, 50, ''),
            ]);
        }

        $history = $room->messages()
            ->orderByDesc('id')
            ->limit(20)
            ->get(['role', 'content'])
            ->reverse()
            ->values()
            ->map(fn ($m) => [
                'role' => $m->role,
                'content' => (string) $m->content,
            ])
            ->all();

        $stockPayload = null;
        $stockContext = null;

        if ($mode === 'stock') {
            $symbolInfo = $this->stockSymbolService->extract($content);

            if ($symbolInfo && ! empty($symbolInfo['symbol'])) {
                $chart = $this->stockMarketService->chart($symbolInfo['symbol']);
                $analysis = $this->stockMarketService->buildAnalysisContext($chart);

                $stockPayload = [
                    'symbol' => $chart['symbol'],
                    'market' => $symbolInfo['market'],
                    'latest_date' => $chart['latest_date'],
                    'latest_open' => $chart['latest_open'],
                    'latest_high' => $chart['latest_high'],
                    'latest_low' => $chart['latest_low'],
                    'latest_close' => $chart['latest_close'],
                    'points' => $chart['points'],
                ];

                $stockContext = [
                    'market' => $symbolInfo['market'],
                    ...$analysis,
                ];
            }
        }

        $ai = $this->openAIService->reply($history, $mode, $stockContext);

        $assistantMessage = ChatMessage::create([
            'chat_room_id' => $room->id,
            'role' => 'assistant',
            'mode' => $mode,
            'content' => $ai['text'],
            'meta' => [
                'provider' => 'openai',
                'model' => config('services.openai.model'),
                'stock' => $stockPayload ? [
                    'symbol' => $stockPayload['symbol'],
                    'market' => $stockPayload['market'],
                    'latest_date' => $stockPayload['latest_date'],
                    'latest_close' => $stockPayload['latest_close'],
                ] : null,
            ],
            'sent_at' => now(),
        ]);

        $room->update([
            'last_mode' => $mode,
            'last_message_at' => now(),
        ]);

        return [
            'mode' => $mode,
            'user_message' => $userMessage,
            'assistant_message' => $assistantMessage,
            'stock' => $stockPayload,
        ];
    }
}
