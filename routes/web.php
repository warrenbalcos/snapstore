<?php

use App\Http\Controllers\KeyValueStoreController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

Route::post('/object', [KeyValueStoreController::class, 'store']);
Route::get('/object/get_all_records', [KeyValueStoreController::class, 'index']);
Route::get('/object/{key}', [KeyValueStoreController::class, 'show']);
