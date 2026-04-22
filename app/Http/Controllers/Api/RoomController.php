<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatRoom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $rooms = ChatRoom::where('user_id', $request->user()->id)
            ->with(['messages' => function ($q) {
                $q->latest('id')->limit(1);
            }])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get()
            ->map(function ($room) {
                $lastMessage = $room->messages->first();

                return [
                    'id' => $room->id,
                    'title' => $room->title,
                    'last_mode' => $room->last_mode,
                    'last_message_at' => $room->last_message_at,
                    'created_at' => $room->created_at,
                    'updated_at' => $room->updated_at,
                    'last_message_preview' => $lastMessage?->content,
                    'last_message_role' => $lastMessage?->role,
                ];
            })
            ->values();

        return response()->json([
            'ok' => true,
            'data' => $rooms,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:100'],
        ]);

        $room = ChatRoom::create([
            'user_id' => $request->user()->id,
            'title' => $data['title'] ?? 'Chat Baru',
            'last_mode' => 'general',
        ]);

        return response()->json([
            'ok' => true,
            'data' => $room,
        ], 201);
    }

    public function show(Request $request, ChatRoom $room): JsonResponse
    {
        abort_unless($room->user_id === $request->user()->id, 403);

        $room->load(['messages' => fn ($q) => $q->orderBy('id')]);

        return response()->json([
            'ok' => true,
            'data' => $room,
        ]);
    }

    public function update(Request $request, ChatRoom $room): JsonResponse
    {
        abort_unless($room->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:100'],
        ]);

        $room->update([
            'title' => $data['title'],
        ]);

        return response()->json([
            'ok' => true,
            'data' => $room->fresh(),
        ]);
    }

    public function destroy(Request $request, ChatRoom $room): JsonResponse
    {
        abort_unless($room->user_id === $request->user()->id, 403);

        $room->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Room berhasil dihapus.',
        ]);
    }
}
