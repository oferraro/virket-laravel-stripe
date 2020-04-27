<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware(['cors'])->group(function () {
    Route::post('/stripe/step1', 'StripeController@step1')->name('stripe.pay');
    Route::post('/stripe/step2', 'StripeController@step2')->name('stripe.pay.step2');


    // Try another path here (using frontend too)
    Route::post('/stripe/demo/step1', 'StripeController@demoStep1')->name('stripe.pay.demo.step1');

    Route::post('/mercadopago/pay', 'MercadoPagoController@pay')->name('mercadopago.pay');
    Route::post('/mercadopago/payments/get', 'MercadoPagoController@paymentsGet')->name('mercadopago.payments.get');

});
