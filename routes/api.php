<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MerchantsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['cors'])->post('/validate_user', [MerchantsController::class, 'validateUser'])->name('validate_user');
Route::middleware(['cors'])->post('/get_merchants', [MerchantsController::class, 'getMerchants'])->name('get_merchants');
Route::middleware(['cors'])->post('/edit_paypal', [MerchantsController::class, 'editPaypalAddress'])->name('edit_paypal');
Route::middleware(['cors'])->post('/get_position', [MerchantsController::class, 'getPosition'])->name('get_position');