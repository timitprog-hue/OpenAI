<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAIService
{
    public function reply(
        array $messages,
        string $mode = 'general',
        ?array $stockContext = null
    ): array {
        $systemPrompt = $mode === 'stock'
            ? $this->stockPrompt($stockContext)
            : $this->generalPrompt();

        $input = [
            [
                'role' => 'system',
                'content' => $systemPrompt,
            ],
        ];

        foreach ($messages as $message) {
            $role = $message['role'] ?? 'user';

            if (! in_array($role, ['system', 'user', 'assistant', 'developer'], true)) {
                $role = 'user';
            }

            $input[] = [
                'role' => $role,
                'content' => (string) ($message['content'] ?? ''),
            ];
        }

        $http = Http::acceptJson()->timeout(60);

        if (! config('services.openai.verify_ssl')) {
            $http = $http->withoutVerifying();
        }

        $response = $http
            ->withToken(config('services.openai.key'))
            ->post('https://api.openai.com/v1/responses', [
                'model' => config('services.openai.model'),
                'input' => $input,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('OpenAI gagal: ' . $response->body());
        }

        $json = $response->json();

        return [
            'text' => $this->extractText($json) ?: 'Maaf, AI tidak memberi balasan.',
            'raw' => $json,
        ];
    }

    protected function extractText(array $json): ?string
    {
        $output = $json['output'] ?? [];

        foreach ($output as $item) {
            $contents = $item['content'] ?? [];

            foreach ($contents as $content) {
                if (($content['type'] ?? null) === 'output_text' && ! empty($content['text'])) {
                    return $content['text'];
                }

                if (! empty($content['text'])) {
                    return $content['text'];
                }
            }
        }

        return null;
    }

    protected function generalPrompt(): string
    {
        return <<<PROMPT
Kamu adalah AI assistant umum.
Jawab jelas, ringkas, ramah, dan berguna.
Jangan membahas saham kecuali user memang bertanya tentang saham.
PROMPT;
    }

    protected function stockPrompt(?array $stockContext = null): string
    {
        $contextText = 'Tidak ada data saham tambahan yang diberikan sistem.';

        if ($stockContext) {
            $contextText = json_encode($stockContext, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        return <<<PROMPT
Kamu adalah AI assistant mode saham.

Aturan:
- Gunakan data saham yang diberikan sistem jika tersedia.
- Jika data sistem sudah tersedia, JANGAN bilang "saya tidak punya data" atau "saya tidak boleh menyebut angka".
- Sebutkan angka hanya jika memang ada di data sistem.
- Jangan mengarang harga, tanggal, atau level teknikal.
- Jika user menanyakan tanggal tertentu, gunakan tanggal trading terbaru yang ada pada data sistem dan jelaskan dengan jujur bila berbeda.
- Fokus pada edukasi, interpretasi data, risiko, dan skenario umum.
- Jangan memberi jaminan profit.

Data sistem saham:
{$contextText}
PROMPT;
    }
}
