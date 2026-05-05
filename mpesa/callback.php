<?php
require_once '../config/dbconnection.php';

// Log raw callback for debugging
file_put_contents('../logs/mpesa_callback.log', date('Y-m-d H:i:s') . ' ' . file_get_contents('php://input') . PHP_EOL, FILE_APPEND);

$data       = json_decode(file_get_contents('php://input'), true);
$result     = $data['Body']['stkCallback'] ?? null;

if (!$result) {
    http_response_code(400);
    exit;
}

$checkoutId = $result['CheckoutRequestID'];
$resultCode = $result['ResultCode'];

$conn = getDBConnection();

if ($resultCode == 0) {
    // ✅ Payment successful
    $mpesaCode = '';
    foreach ($result['CallbackMetadata']['Item'] as $item) {
        if ($item['Name'] === 'MpesaReceiptNumber') {
            $mpesaCode = $item['Value'];
        }
    }

    // Activate subscription for 30 days
    $stmt = $conn->prepare("UPDATE subscriptions SET 
        status       = 'active',
        mpesa_code   = ?,
        start_date   = CURDATE(),
        expiry_date  = DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        WHERE checkout_request_id = ?");
    $stmt->bind_param('ss', $mpesaCode, $checkoutId);
    $stmt->execute();
    $stmt->close();

    // Activate the business listing
    $stmt2 = $conn->prepare("UPDATE businesses SET status = 'active' 
        WHERE id = (SELECT business_id FROM subscriptions WHERE checkout_request_id = ?)");
    $stmt2->bind_param('s', $checkoutId);
    $stmt2->execute();
    $stmt2->close();

} else {
    // ❌ Payment failed
    $stmt = $conn->prepare("UPDATE subscriptions SET status = 'failed' 
        WHERE checkout_request_id = ?");
    $stmt->bind_param('s', $checkoutId);
    $stmt->execute();
    $stmt->close();
}

closeDBConnection($conn);
http_response_code(200);
echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);