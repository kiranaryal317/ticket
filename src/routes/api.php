<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\CommentController;




Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});

Route::middleware('role:Admin')->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::patch('/users/{user}/role', [UserController::class, 'assignRole']);
    Route::get('/roles', [UserController::class, 'roles']);
});


Route::get('/tickets', [TicketController::class, 'index']);
Route::post('/tickets', [TicketController::class, 'store']);
Route::get('/tickets/{ticket}', [TicketController::class, 'show']);

Route::middleware('role:Staff|Admin')->group(function () {
    Route::patch('/tickets/{ticket}/status', [TicketController::class, 'updateStatus']);
});

Route::middleware('role:Admin')->group(function () {
    Route::patch('/tickets/{ticket}/assign', [TicketController::class, 'assign']);
});

Route::get('/tickets/{ticket}/comments', [CommentController::class, 'index']);
Route::post('/tickets/{ticket}/comments', [CommentController::class, 'store']);