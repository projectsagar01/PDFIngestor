<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\DocumentController;

Route::get('/', function () {
    return view('welcome');
});
Route::post('/documents/upload', [DocumentController::class, 'upload']);

Route::post('/documents/upload', [DocumentController::class, 'upload']);
Route::get('/documents/{id}/status', [DocumentController::class, 'status']);
Route::get('/search', [DocumentController::class, 'search']);
Route::get('/test-csrf', function () {
    return response()->json(['message' => 'CSRF working']);
});