<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});


// http://localhost:8000/mercadopago/acreditar-ticket/25060910
Route::get('mercadopago/acreditar-ticket/{id}', 'MercadoPagoController@updateOxxoPayment')
    ->name('mercadopago.ticket.pay');

// http://localhost:8000/mercadopago/ticket/25060910
Route::get('mercadopago/ticket/{id}', 'MercadoPagoController@oxxoGetPayment')
    ->name('mercadopago.ticket.get');
