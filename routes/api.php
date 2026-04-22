<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\StockController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'ok' => true,
        'message' => 'API Laravel jalan',
    ]);
});

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/rooms', [RoomController::class, 'index']);
    Route::post('/rooms', [RoomController::class, 'store']);
    Route::get('/rooms/{room}', [RoomController::class, 'show']);
    Route::delete('/rooms/{room}', [RoomController::class, 'destroy']);
    Route::patch('/rooms/{room}', [RoomController::class, 'update']);

    Route::post('/rooms/{room}/messages', [MessageController::class, 'store']);
    Route::get('/rooms/{room}/messages', [MessageController::class, 'index']);

    Route::get('/stocks/chart', [StockController::class, 'chart']);
});
