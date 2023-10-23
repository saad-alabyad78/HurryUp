<?php

use Illuminate\Http\Request;
use App\Http\Controllers\VerticesController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EdgesController;
use App\Http\Controllers\BusLineController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});



Route::middleware('guest:api')->group(function () {
    Route::post('/login', [UserController::class, 'login']);
    Route::post('/register', [UserController::class, 'register']);
    });

Route::middleware('jwt.auth')->group(function () {
Route::post('/logout', [UserController::class, 'logout']);
Route::get('/id', [UserController::class, 'getid']);

Route::delete('/delete/order', [OrderController::class, 'destroy']);
Route::resource('orders', OrderController::class);
Route::post('/orders/join', [OrderController::class, 'joinOrder']);
Route::post('/complete/order', [OrderController::class, 'completeOrder']);
Route::post('/feedback', [VerticesController::class, 'feedback']);
Route::get('/history', [OrderController::class, 'gethistory'])->name('order.history');
Route::get('/pending/orders', [OrderController::class, 'getPendingOrders']);
Route::post('/shortest-path', [EdgesController::class, 'findShortestPath']);
Route::post('/orders/destination', [OrderController::class, 'getOrdersBydestnaion']);
Route::post('orders/gender', [OrderController::class, 'getOrdersBygender']);
Route::get('/profile', [UserController::class, 'getprofile']);
Route::post('/search', [OrderController::class, 'search']);

});



//dashboard
Route::prefix('admin')->group(function () {
    Route::delete('/vertices', [VerticesController::class, 'destroy']);

    Route::resource('vertices', VerticesController::class);
    Route::resource('edges', EdgesController::class);
    Route::get('/users', [UserController::class, 'index']);
    Route::delete('/busline', [BusLineController::class, 'destroy']);
    Route::resource('busline', BusLineController::class);



});































