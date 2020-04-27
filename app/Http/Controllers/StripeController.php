<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StripeController extends Controller
{

    public function __construct() {
        \Stripe\Stripe::setApiKey(env('STRIPE_API_KEY'));
    }

    public function step1(Request $request) {

        $paymentMethod = \Stripe\PaymentMethod::retrieve($request->get('cardToken')['id']);

        $intent = \Stripe\PaymentIntent::create([
            'amount' => 1099,
            'currency' => 'usd',
            'payment_method_types' => ['card'],
            'statement_descriptor' => 'Custom descriptor',
            'metadata' => [
                'integration_check' => 'accept_a_payment'
            ]
        ]);

        $userStripeCustomer = \Stripe\Customer::create([
            'email' => 'test@email.com',
            'name' => 'test',
        ]);
        $userStripeCustomer = \Stripe\Customer::update($userStripeCustomer->id, [
            'source' => $request->get('cardToken')['id'],
        ]);


        /*$userStripeCustomer = \Stripe\Customer::update($userStripeCustomer->id, [
            'invoice_settings' => [
                'default_payment_method' => $paymentMethod
            ]
        ]);*/

        $charge = \Stripe\Charge::create([
            'amount' => 1099,
            'currency' => 'eur',
            'customer' => $userStripeCustomer->id,
            //'source' => $paymentMethod->id,
        ]);


        return response()->json([
            'paymentMethod' => $paymentMethod,
            'userStripeCustomer' => $userStripeCustomer,
            'intent' => $intent,
            'charge' => $charge,
            'cardToken' => $request->get('cardToken'),
        ], 200); //Make sure your response is there

    }

    public function step2(Request $request) {
        // 'cardToken' => $request->get('cardToken')['id'],
        // $order->pay(['source' => $request->get('cardToken')]);
        return response()->json([
            'requestAll' => $request->all(),
        ]);
    }

    /* DEMO, use path integrating with frontend from here */
    public function demoStep1(Request $request) {
        $intent = \Stripe\PaymentIntent::create([
            'amount' => $request->get('price'),
            'currency' => 'usd',
            // Verify your integration in this guide by including this parameter
            'metadata' => ['integration_check' => 'accept_a_payment'],
        ]);
        return response()->json([
            'intent' => $intent,
        ]);
    }

}
