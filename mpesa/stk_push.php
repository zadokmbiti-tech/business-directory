<?php
require_once '../config/dbconnection.php';
require_once '../config/mpesa.php';

function getAccessToken() {
    $credentials = base64_encode(CONSUMER_KEY . ':' . CONSUMER_SECRET);
    $url = BASE_URL . '/oauth/v1/generate?grant_type=client_credentials';

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $credentials]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);

    return $response['access_token'] ?? null;
}

function stkPush($phone, $amount, $business_id, $plan_id) {
    $token     = getAccessToken();
    $timestamp = date('YmdHis');
    $password  = base64_encode(SHORTCODE . PASSKEY . $timestamp);

    // Format phone: 0799867545 → 254799867545
    $phone = '254' . ltrim($phone, '0');

    $payload = [
        'BusinessShortCode' => SHORTCODE,
        'Password'          => $password,
        'Timestamp'         => $timestamp,
        'TransactionType'   => 'CustomerPayBillOnline',
        'Amount'            => (int)$amount,
        'PartyA'            => $phone,
        'PartyB'            => SHORTCODE,
        'PhoneNumber'       => $phone,
        'CallBackURL'       => CALLBACK_URL,
        'AccountReference'  => 'Zaddy-' . $business_id,
        'TransactionDesc'   => 'Zaddy Business Listing Subscription'
    ];

    $ch = curl_init(BASE_URL . '/mpesa/stkpush/v1/processrequest');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);

    // Save pending subscription to DB
    if (isset($response['CheckoutRequestID'])) {
        $conn = getDBConnection();
        $stmt = $conn->prepare("INSERT INTO subscriptions 
            (business_id, plan_id, amount, checkout_request_id, status)
            VALUES (?, ?, ?, ?, 'pending')");
        $stmt->bind_param('iids', $business_id, $plan_id, $amount, $response['CheckoutRequestID']);
        $stmt->execute();
        $stmt->close();
        closeDBConnection($conn);
    }

    return $response;
}

// Handle form POST from payment page
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone       = $_POST['phone'] ?? '';
    $plan_id     = (int)($_POST['plan_id'] ?? 0);
    $business_id = (int)($_POST['business_id'] ?? 0);

    // Get plan amount
    $conn   = getDBConnection();
    $stmt   = $conn->prepare("SELECT price FROM plans WHERE id = ?");
    $stmt->bind_param('i', $plan_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $amount = $result['price'] ?? 0;
    $stmt->close();
    closeDBConnection($conn);

    $response = stkPush($phone, $amount, $business_id, $plan_id);

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}