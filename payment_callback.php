<?php
require_once 'includes/functions.php';
require_once 'config/database.php';
require_once 'includes/payment_gateway.php';

// This file handles callbacks from payment gateways
// It should be accessible without authentication

$database = new Database();
$db = $database->getConnection();
$payment_gateway = new PaymentGateway($db);

// Get the request body
$request_body = file_get_contents('php://input');
$callback_data = json_decode($request_body);

// Log the callback for debugging
file_put_contents('payment_callback_log.txt', date('Y-m-d H:i:s') . ' - ' . $request_body . PHP_EOL, FILE_APPEND);

// Handle M-Pesa callback
if (isset($callback_data->Body->stkCallback)) {
    $result_code = $callback_data->Body->stkCallback->ResultCode;
    $checkout_request_id = $callback_data->Body->stkCallback->CheckoutRequestID;
    
    // Find the payment by checkout request ID
    $query = "SELECT * FROM payments WHERE transaction_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$checkout_request_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($payment) {
        $order_id = $payment['order_id'];
        
        if ($result_code == 0) {
            // Payment successful
            $payment_gateway->updatePaymentStatus($order_id, 'completed', $checkout_request_id);
        } else {
            // Payment failed
            $payment_gateway->updatePaymentStatus($order_id, 'failed', $checkout_request_id);
        }
    }
    
    // Return response to M-Pesa
    header('Content-Type: application/json');
    echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Callback received successfully']);
    exit;
}

// Handle Stripe webhook
if (isset($_SERVER['HTTP_STRIPE_SIGNATURE'])) {
    require_once 'vendor/autoload.php';
    
    $endpoint_secret = 'whsec_abcdefghijklmnopqrstuvwxyz12345678901234';
    
    try {
        $event = \Stripe\Webhook::constructEvent(
            $request_body, $_SERVER['HTTP_STRIPE_SIGNATURE'], $endpoint_secret
        );
        
        if ($event->type === 'charge.succeeded') {
            $charge = $event->data->object;
            $order_id = $charge->metadata->order_id;
            
            // Update payment status
            $payment_gateway->updatePaymentStatus($order_id, 'completed', $charge->id);
        } elseif ($event->type === 'charge.failed') {
            $charge = $event->data->object;
            $order_id = $charge->metadata->order_id;
            
            // Update payment status
            $payment_gateway->updatePaymentStatus($order_id, 'failed', $charge->id);
        }
        
        http_response_code(200);
        exit;
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

// If we get here, it's an unknown callback
http_response_code(400);
echo json_encode(['error' => 'Unknown callback type']);
?>
