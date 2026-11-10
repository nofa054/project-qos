<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PrediksiController;

Route::get('/', [DashboardController::class, 'index']);

Route::get('/prediksi', [PrediksiController::class, 'index']);
Route::post('/prediksi', [PrediksiController::class, 'proses']);