<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \MercadoPago;
use Illuminate\Support\Facades\Http;

class MercadoPagoController extends Controller
{

    private function getOrCreateCustomer($formData) {
        $filters = [
            "email" => $formData['email']
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
        $mpAccessToken = env("MERCADO_PAGO_ENV_ACCESS");
        MercadoPago\SDK::setAccessToken($mpAccessToken);               // Set MercadoPago keys
        $formData = $request->get('formData');
        // Use the payment Token
        $paymentToken = $request->get('MercadopagoPaymentId');
        // Get or create customer and customer card
        $customer = $this->getOrCreateCustomer($formData);
        $card = $this->setOrUpdateCustomerCard($paymentToken, $customer);
        // get card attributes
        $cardAttributes = $card->getAttributes();
        $cardIssuerAttributes = $cardAttributes['issuer'];
        $cardAttributesPayMethod = $cardAttributes['payment_method'];
        // Use the datat to create the payment
        $payment = new MercadoPago\Payment();
        $payment->metadata = [
            "order_id" => time(),
        ];
        $payment->transaction_amount = $formData['cartAmount'];         // $formData['cartAmount'];
        $payment->token = $paymentToken;
        $payment->description = "Ergonomic Paper Plate"; // TODO: Add the proper description here
        $payment->installments = 1; // TODO: Ask installments (cuotas) in frontend? use the API? use always just 1?
        $payment->payment_method_id = $cardAttributesPayMethod->id;
        $payment->issuer_id = $cardIssuerAttributes->id; // card issuer
        $payment->payer = [
            "email" => $customer->email // TODO: use more customer info here? add orderID?
        ];
        $payment->save();

        return response()->json([
            'MercadopagoPaymentId' => $paymentToken,
            'status' => $payment->status,
            'payment' =>  $payment,
            'request' => $request->all()
        ]);
    }

    public function paymentsGet() {
        $mpAccessToken = env("MERCADO_PAGO_ENV_ACCESS");
        MercadoPago\SDK::setAccessToken($mpAccessToken);

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
        $url = "https://api.mercadopago.com/v1/payments/search?access_token=$mpAccessToken&$getParams";
        error_log(print_r($url, 1), 3, '/tmp/log');
        $response = Http::get($url)->json();

        return response()->json([
            'payments' => $response,
        ]);
    }
}

/*
curl -G -X GET -H "accept: application/json" "https://api.mercadopago.com/v1/payments/search" \
-d "access_token=TEST-7577306854336871-052219-d510527538af0fee546eb6e583f08fcb__LA_LB__-185098070" \
-d "status=approved" -d "offset=0" -d "limit=3" -d "sort=id" -d "criteria=desc"
*/
