<?php

use App\Http\Controllers\EnquiryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/enquiries', [EnquiryController::class, 'index']);
Route::post('/enquiries', [EnquiryController::class, 'store']);
Route::patch('/enquiries/{enquiry}/status', [EnquiryController::class, 'updateStatus']);
