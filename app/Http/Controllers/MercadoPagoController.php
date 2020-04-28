<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \MercadoPago;
use Illuminate\Support\Facades\Http;

class MercadoPagoController extends Controller
{
    public $mpAccessToken = '';

    public function __construct() {
        // $this->mpAccessToken = env("MERCADO_PAGO_ENV_ACCESS");
        $this->mpAccessToken = env("MERCADO_PAGO_ENV_ACCESS_MX");
        MercadoPago\SDK::setAccessToken($this->mpAccessToken);
    }

    private function getOrCreateCustomer($formData) {
        $filters = [
            "email" => strtolower($formData['email'])
        ];
        $customers = MercadoPago\Customer::search($filters);
        if(isset($customers[0])) {
            $customer = $customers[0];
        } else {
            $customer = new MercadoPago\Customer();
            $customer->email = $formData['email'];
            $customer->save();
        }
        return $customer;
    }

    private function setOrUpdateCustomerCard($paymentToken, $customer) {
        $card = new MercadoPago\Card();
        $card->token = $paymentToken;
        $card->customer_id = $customer->id;
        $card->save();
        return $card;
    }

    public function pay(Request $request) {
        $formData = $request->get('formData');
        // Use the payment Token
        $paymentToken = $request->get('MercadopagoPaymentId');
        // Get or create customer and customer card
        $customer = $this->getOrCreateCustomer($formData);
        // Check if user has cards
        if (isset($customer->cards[0])) { // TODO: manage multiple cards, not only one
            // User has at least one card, use the first one
            $customerCardsUrl = "https://api.mercadopago.com/v1/customers/".$customer->id."?access_token=".$this->mpAccessToken;
            $customerCards = json_decode(Http::get($customerCardsUrl)->body());
            $cardAttributes = $customerCards->cards[0];
            $cardPaymentMethodId = $cardAttributes->payment_method->id; // user name i.e. master
            $cardIssuerId = $cardAttributes->issuer->id; // Use card issuer ID here i.e. 3
        } else {
            // Customer has no cards (create new card)
            $card = $this->setOrUpdateCustomerCard($paymentToken, $customer);
            $cardAttributes = $card->getAttributes();
            $cardAttributesPayMethod = $cardAttributes['payment_method'];
            if (isset($cardAttributes['error'])) {
                return response()->json([
                    'error' => $cardAttributes['error'],
                ]); // FINISH HERE, error
            }
            $cardPaymentMethodId = $cardAttributesPayMethod->id;
            $cardIssuerAttributes = $cardAttributes['issuer'];
            $cardIssuerId = $cardIssuerAttributes->id;
        }

        // Use the datat to create the payment
        $payment = new MercadoPago\Payment();
        $payment->metadata = [
            "order_id" => time(),
        ];
        $payment->transaction_amount = $formData['cartAmount'];         // $formData['cartAmount'];
        $payment->token = $paymentToken;
        $payment->description = "Ergonomic Paper Plate"; // TODO: Add the proper description here
        $payment->installments = 1; // TODO: Ask installments (cuotas) in frontend? use the API? use always just 1?
        $payment->payment_method_id = $cardPaymentMethodId;
        $payment->issuer_id = $cardIssuerId;  // card issuer
        $payment->payer = [
            "email" => $customer->email // TODO: use more customer info here? add orderID?
        ];
        $payment->save();

        return response()->json([
            'customer' => $customer,
            'MercadopagoPaymentId' => $paymentToken,
            'status' => $payment->status,
            'payment' =>  $payment,
            'request' => $request->all()
        ]);
    }

    public function paymentsGet() {

        /*  MercadoPago api doesn't work (use Guzzle)
            $payments = MercadoPago\SDK::get(
            "/v1/payments/search",
                [ // TODO: define pagination if required
                    "status" => "approved",
                    "offset" => "0",
                    "limit" => "3",
                    "sort" => "id",
                    "criteria" => "desc"
                ]
            );
        */

        $params = [
            // TODO: define pagination if required
            "status" => "approved",
            "offset" => "0",
            "limit" => "3",
            "sort" => "id",
            "criteria" => "desc",
        ];
        $getParams = '';
        foreach ($params as $k => $v) {
            $getParams.="&$k=$v";
        }
        $url = "https://api.mercadopago.com/v1/payments/search?access_token=".$this->mpAccessToken."&$getParams";
        $response = Http::get($url)->json();

        return response()->json([
            'payments' => $response,
        ]);
    }

    public function customerSearch(Request $request) {
        $customer = $this->getOrCreateCustomer([
            'email' => $request->get('customer')['email']
        ]);

        $customerCardsUrl = "https://api.mercadopago.com/v1/customers/".$customer->id."?access_token=".$this->mpAccessToken;
        $customerCards = Http::get($customerCardsUrl)->json();
        return response()->json([
            'customer' => (($customer) ? $customer->toArray() : []),
            'cards' => $customerCards
        ]);
    }


    public function oxxoPay (Request $request) {
        $paymentMethods = $this->getPaymentMethods();
        foreach($paymentMethods as $paymentMethod) { // Just to know the proper oxxo name
            if (strstr(strtolower($paymentMethod['name']), 'oxxo')) {
                error_log(print_r($paymentMethod, 1), 3, '/tmp/log');
            }
        }

        $payment = new MercadoPago\Payment();
        $payment->transaction_amount = 100;
        $payment->description = "Título del producto";
        $payment->payment_method_id = "oxxo";
        $payment->payer = [
            "email" => time()."@testuser.com"
        ];
        $payment->save();

        return response()->json([
            'payment' => $payment->toArray(),
            'request' => $request->all()
        ]);
    }

    private function getPaymentMethods() {
        $url = "https://api.mercadopago.com/v1/payment_methods?access_token=".$this->mpAccessToken;
        return Http::get($url)->json();;
    }

    public function oxxoGetPayment($ticketId) {
        $url = "https://api.mercadopago.com/v1/payments/$ticketId?access_token=".$this->mpAccessToken;
        $response = Http::get($url)->json();
        dd($response);
    }

    public function updateOxxoPayment($ticketId) { // $ticketId = "25060486";
        $url = "https://api.mercadopago.com/v1/payments/$ticketId?access_token=".$this->mpAccessToken;
        $response = Http::put($url, [
            'status' => "approved"
        ])->json();
        dd($response);
    }

}

/*
curl -G -X GET -H "accept: application/json" "https://api.mercadopago.com/v1/payments/search" \
-d "access_token=TEST-7577306854336871-052219-d510527538af0fee546eb6e583f08fcb__LA_LB__-185098070" \
-d "status=approved" -d "offset=0" -d "limit=3" -d "sort=id" -d "criteria=desc"
*/
