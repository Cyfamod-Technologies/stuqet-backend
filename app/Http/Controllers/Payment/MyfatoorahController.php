<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\CombinedOrder;
use App\Models\Currency;
use App\Models\CustomerPackage;
use App\Models\Order;
use App\Models\SellerPackage;
use Illuminate\Http\Request;
use MyFatoorah\Library\PaymentMyfatoorahApiV2;
use Redirect;
use Session;

class MyfatoorahController extends Controller
{
    protected ?PaymentMyfatoorahApiV2 $mfObj = null;

    public function __construct()
    {
    }

    protected function initializeGateway(): void
    {
        if ($this->mfObj) {
            return;
        }

        $this->mfObj = new PaymentMyfatoorahApiV2(
            env('MYFATOORAH_TOKEN'),
            env('MYFATOORAH_COUNTRY_ISO'),
            get_setting('myfatoorah_sandbox') == 1
        );
    }

    public function pay(Request $request)
    {
        $this->initializeGateway();

        if (! auth()->check() || ! Session::has('payment_type')) {
            flash(translate('Unable to initialize payment.'))->error();
            return redirect()->route('home');
        }

        $paymentType = Session::get('payment_type');
        $paymentData = Session::get('payment_data', []);
        $user = auth()->user();
        $amount = 0;
        $customerReference = null;

        if ($paymentType === 'cart_payment') {
            $combinedOrder = CombinedOrder::findOrFail(Session::get('combined_order_id'));
            $amount = $combinedOrder->grand_total;
            $customerReference = $paymentType . '-' . $combinedOrder->id . '-' . $user->id;
        } elseif ($paymentType === 'order_re_payment') {
            $order = Order::findOrFail($paymentData['order_id']);
            $amount = $order->grand_total;
            $customerReference = $paymentType . '-' . $order->id . '-' . $user->id;
        } elseif ($paymentType === 'wallet_payment') {
            $amount = $paymentData['amount'];
            $customerReference = $paymentType . '-' . $amount . '-' . $user->id;
        } elseif ($paymentType === 'customer_package_payment') {
            $customerPackage = CustomerPackage::findOrFail($paymentData['customer_package_id']);
            $amount = $customerPackage->amount;
            $customerReference = $paymentType . '-' . $customerPackage->id . '-' . $user->id;
        } elseif ($paymentType === 'seller_package_payment') {
            $sellerPackage = SellerPackage::findOrFail($paymentData['seller_package_id']);
            $amount = $sellerPackage->amount;
            $customerReference = $paymentType . '-' . $sellerPackage->id . '-' . $user->id;
        }

        if (! $customerReference || $amount <= 0) {
            flash(translate('Unable to initialize payment.'))->error();
            return redirect()->route('home');
        }

        $currencyCode = Currency::findOrFail(get_setting('system_default_currency'))->code;
        $paymentMethodId = 0;
        $callbackUrl = route('myfatoorah.callback');

        $data = [
            'InvoiceValue' => $amount,
            'DisplayCurrencyIso' => $currencyCode,
            'CallBackUrl' => $callbackUrl,
            'ErrorUrl' => $callbackUrl,
            'paymentMethodId' => $paymentMethodId,
            'CustomerName' => $user->name,
            'CustomerEmail' => $user->email ?? 'customer@example.com',
            'MobileCountryCode' => '+965',
            'CustomerMobile' => preg_replace('/\D+/', '', (string) ($user->phone ?? '12345678')) ?: '12345678',
            'Language' => 'en',
            'CustomerReference' => $customerReference,
        ];

        try {
            $invoice = $this->mfObj->getInvoiceURL($data, $paymentMethodId);

            if (! empty($invoice['invoiceId']) && ! empty($invoice['invoiceURL'])) {
                return Redirect::to($invoice['invoiceURL']);
            }
        } catch (\Throwable $exception) {
        }

        flash(translate('Payment failed or got cancelled'))->error();
        return redirect()->route('home');
    }

    public function callback(Request $request)
    {
        $this->initializeGateway();

        try {
            $response = $this->mfObj->getPaymentStatus($request->paymentId, 'PaymentId');
        } catch (\Throwable $exception) {
            flash(translate('Payment failed or got cancelled'))->error();
            return redirect()->route('home');
        }

        if (($response->InvoiceStatus ?? null) !== 'Paid' || empty($response->CustomerReference)) {
            flash(translate('Payment failed or got cancelled'))->error();
            return redirect()->route('home');
        }

        $reference = explode('-', $response->CustomerReference);
        $paymentType = $reference[0] ?? null;
        $referenceId = $reference[1] ?? null;
        $userId = $reference[2] ?? null;
        $paymentDetails = json_encode($response);

        if (! $paymentType || ! $referenceId) {
            flash(translate('Payment failed or got cancelled'))->error();
            return redirect()->route('home');
        }

        if ($paymentType === 'cart_payment') {
            checkout_done($referenceId, $paymentDetails);
            Session::forget('payment_type');
            Session::forget('payment_data');
            Session::put('combined_order_id', $referenceId);

            return redirect()->route('order_confirmed');
        }

        if ($paymentType === 'order_re_payment') {
            order_re_payment_done($referenceId, 'My Fatoorah', $paymentDetails);
            Session::forget('payment_type');
            Session::forget('payment_data');

            flash(translate('Payment done.'))->success();
            return redirect()->route('purchase_history.details', encrypt($referenceId));
        }

        if ($paymentType === 'wallet_payment' && $userId) {
            wallet_payment_done($userId, $referenceId, 'My Fatoorah', $paymentDetails);
            Session::forget('payment_type');
            Session::forget('payment_data');

            flash(translate('Recharge completed'))->success();
            return redirect()->route('wallet.index');
        }

        if ($paymentType === 'customer_package_payment' && $userId) {
            customer_purchase_payment_done($userId, $referenceId, 'My Fatoorah', $paymentDetails);
            Session::forget('payment_type');
            Session::forget('payment_data');

            flash(translate('Package purchasing successful'))->success();
            return redirect()->route('dashboard');
        }

        if ($paymentType === 'seller_package_payment' && $userId) {
            seller_purchase_payment_done($userId, $referenceId, 'My Fatoorah', $paymentDetails);
            Session::forget('payment_type');
            Session::forget('payment_data');

            flash(translate('Package purchasing successful'))->success();
            return redirect()->route('dashboard');
        }

        flash(translate('Payment failed or got cancelled'))->error();
        return redirect()->route('home');
    }
}
