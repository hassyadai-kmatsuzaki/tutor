<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\PropertyMatchController;
use App\Http\Controllers\DashboardController;

// 認証不要のルート
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// 認証が必要なルート
Route::middleware('auth:sanctum')->group(function () {
    
    // ダッシュボード
    Route::prefix('dashboard')->group(function () {
        Route::get('/stats', [DashboardController::class, 'stats']);
        Route::get('/activities', [DashboardController::class, 'activities']);
        Route::get('/alerts', [DashboardController::class, 'alerts']);
        Route::get('/sales-analysis', [DashboardController::class, 'salesAnalysis']);
    });

    // ユーザー管理（管理者のみ）
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('/statistics', [UserController::class, 'statistics']);
        Route::get('/{user}', [UserController::class, 'show']);
        Route::put('/{user}', [UserController::class, 'update']);
        Route::delete('/{user}', [UserController::class, 'destroy']);
    });

    // 物件管理
    Route::prefix('properties')->group(function () {
        Route::get('/', [PropertyController::class, 'index']);
        Route::post('/', [PropertyController::class, 'store']);
        Route::get('/statistics', [PropertyController::class, 'statistics']);
        Route::post('/import', [PropertyController::class, 'import']);
        
        Route::prefix('{property}')->group(function () {
            Route::get('/', [PropertyController::class, 'show']);
            Route::put('/', [PropertyController::class, 'update']);
            Route::delete('/', [PropertyController::class, 'destroy']);
            
            // 画像管理
            Route::get('/images', [PropertyController::class, 'getImages']);
            Route::post('/images', [PropertyController::class, 'uploadImage']);
            Route::delete('/images/{imageId}', [PropertyController::class, 'deleteImage']);
        });
    });

    // 顧客管理
    Route::prefix('customers')->group(function () {
        Route::get('/', [CustomerController::class, 'index']);
        Route::post('/', [CustomerController::class, 'store']);
        Route::get('/statistics', [CustomerController::class, 'statistics']);
        Route::get('/by-assignee', [CustomerController::class, 'byAssignee']);
        
        Route::prefix('{customer}')->group(function () {
            Route::get('/', [CustomerController::class, 'show']);
            Route::put('/', [CustomerController::class, 'update']);
            Route::delete('/', [CustomerController::class, 'destroy']);
            
            // 詳細条件管理
            Route::get('/preferences', [CustomerController::class, 'getPreferences']);
            Route::put('/preferences', [CustomerController::class, 'updatePreferences']);
            
            // 活動管理
            Route::get('/activities', [CustomerController::class, 'getActivities']);
            Route::post('/activities', [CustomerController::class, 'addActivity']);
            Route::post('/contact', [CustomerController::class, 'recordContact']);
        });
    });

    // マッチング管理
    Route::prefix('matches')->group(function () {
        Route::get('/', [PropertyMatchController::class, 'index']);
        Route::post('/generate', [PropertyMatchController::class, 'generate']);
        Route::get('/statistics', [PropertyMatchController::class, 'statistics']);
        
        Route::prefix('{match}')->group(function () {
            Route::get('/', [PropertyMatchController::class, 'show']);
            Route::put('/', [PropertyMatchController::class, 'update']);
            Route::put('/status', [PropertyMatchController::class, 'updateStatus']);
            Route::post('/notes', [PropertyMatchController::class, 'addNote']);
            Route::delete('/', [PropertyMatchController::class, 'destroy']);
            Route::post('/present', [PropertyMatchController::class, 'present']);
            Route::post('/response', [PropertyMatchController::class, 'recordResponse']);
        });
    });

    // 推奨機能
    Route::prefix('recommendations')->group(function () {
        Route::get('/properties/{property}/customers', [PropertyMatchController::class, 'getRecommendedCustomers']);
        Route::get('/customers/{customer}/properties', [PropertyMatchController::class, 'getRecommendedProperties']);
    });
}); 