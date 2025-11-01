<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PropertyController;

// 認証不要のルート
Route::post('api/login', [AuthController::class, 'login']);
Route::post('api/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('api/me', [AuthController::class, 'me'])->middleware('auth:sanctum');

// 公開物件検索ページ（認証不要）
Route::get('/properties/search', [PropertyController::class, 'publicSearch']);
Route::get('/api/public/properties/search', [PropertyController::class, 'publicSearchApi']);
Route::get('/api/public/properties/{id}', [PropertyController::class, 'publicShow']);

// Reactアプリケーションのルート（APIルートを除外）
Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!api|properties).*');
