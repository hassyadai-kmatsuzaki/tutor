<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// 認証不要のルート
Route::post('api/login', [AuthController::class, 'login']);
Route::post('api/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('api/me', [AuthController::class, 'me'])->middleware('auth:sanctum');

// Reactアプリケーションのルート（APIルートを除外）
Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!api).*');
