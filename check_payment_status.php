<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

// This endpoint checks the payment status for AJAX requests

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if (!$order_id) {
    echo json_encode(['error' => 'Invalid order ID']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Verify order belongs to current user
$query = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo json_encode(['error' => 'Order not found']);
    exit;
}

// Get payment status
$payment_query = "SELECT * FROM payments WHERE order_id = ? ORDER BY id DESC LIMIT 1";
$payment_stmt = $db->prepare($payment_query);
$payment_stmt->execute([$order_id]);
$payment = $payment_stmt->fetch(PDO::FETCH_ASSOC);

if (!$payment) {
    echo json_encode(['status' => 'pending']);
    exit;
}

echo json_encode(['status' => $payment['status']]);
?>
