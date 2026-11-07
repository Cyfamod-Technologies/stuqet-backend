<?php

namespace App\Http\Controllers\Api\V2;

use Illuminate\Http\Request;

class PaymentTypesController
{
    public function getList(Request $request)
    {
        if ($request->input('list', 'both') === 'offline') {
            return response()->json([]);
        }

        $methods = get_activate_payment_methods()->filter(fn ($method) =>
            in_array($method->name, ['paystack', 'flutterwave'], true)
        );
        $wallet = $request->input('mode', 'order') === 'wallet';

        return response()->json($methods->map(function ($method) use ($wallet) {
            return [
                'payment_type' => $method->name,
                'payment_type_key' => $method->name,
                'image' => static_asset('assets/img/cards/'.$method->name.'.png'),
                'name' => ucfirst($method->name),
                'title' => $wallet ? translate('Recharge with '.$method->name) : translate($method->name),
                'offline_payment_id' => 0,
                'details' => '',
            ];
        })->values());
    }
}
