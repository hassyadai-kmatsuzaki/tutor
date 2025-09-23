<?php

use Illuminate\Support\Facades\Route;

// Reactアプリケーションのルート（APIルートを除外）
Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!api).*');
