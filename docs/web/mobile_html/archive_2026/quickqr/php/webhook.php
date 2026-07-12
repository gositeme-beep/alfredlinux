<?php
// Security: whitelist payment folders to prevent path traversal/LFI
$allowed_payments = ['2checkout','ccavenue','flutterwave','iyzico','midtrans','mollie','paypal','paystack','paytabs','paytm','payumoney','razorpay','stripe','telr','wire_transfer'];
if(isset($match['params']['i'])){
    $payment_folder = $match['params']['i'];
    if (in_array($payment_folder, $allowed_payments, true) && file_exists('includes/payments/' . $payment_folder . '/webhook.php')) {
        require_once('includes/payments/' . $payment_folder . '/webhook.php');
    }
}
die();