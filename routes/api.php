<?php

use App\Http\Controllers\SisterAuthController;
use App\Http\Controllers\SisterReferensiController;
use Illuminate\Support\Facades\Route;

Route::post('/sister/token', [SisterAuthController::class, 'getToken']);
Route::get('/sister/referensi/sdm', [SisterReferensiController::class, 'sdm']);
