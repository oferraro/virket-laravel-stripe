<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StripeController extends Controller
{

    public function step1() {
        \Stripe\Stripe::setApiKey(env('STRIPE_API_KEY'));

        $setup_intent = \Stripe\SetupIntent::create([
            'usage' => 'on_session'
        ]);

        $userStripeCustomer = \Stripe\Customer::create([
            'email' => 'test@email.com',
            'name' => 'test',
        ]);

        $paymentMethod = \Stripe\PaymentMethod::create([
            'type' => 'card',
            'card' => [
                'number' => '4242424242424242',
                'exp_month' => 4,
                'exp_year' => 2021,
                'cvc' => '314',
            ],
        ]);

        $intent = \Stripe\PaymentIntent::create([
            'payment_method' => $paymentMethod,
            'amount' => 350,
            'currency' => 'eur',
            'confirmation_method' => 'manual',
            'confirm' => true,
            'customer' => $userStripeCustomer->id,
        ]);

        return response()->json([
            'data' => $userStripeCustomer,
            'intent' => $intent], 200
        ); //Make sure your response is there

    }
}
