<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;


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