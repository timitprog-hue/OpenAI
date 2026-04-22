<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatRoom;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request, ChatRoom $room): JsonResponse
    {
        abort_unless($room->user_id === $request->user()->id, 403);

        $perPage = min((int) $request->get('per_page', 20), 50);

        $messages = $room->messages()
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'ok' => true,
            'data' => $messages->items(),
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
                'has_more' => $messages->hasMorePages(),
            ],
        ]);
    }

    public function store(Request $request, ChatRoom $room, ChatService $chatService): JsonResponse
    {
        abort_unless($room->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $result = $chatService->sendUserMessage($room, $data['content']);

        return response()->json([
            'ok' => true,
            'mode' => $result['mode'],
            'user_message' => $result['user_message'],
            'assistant_message' => $result['assistant_message'],
            'stock' => $result['stock'],
        ]);
    }
}
