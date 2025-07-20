<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();

// Get user data
$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = $db->prepare($user_query);
$user_stmt->execute([$_SESSION['user_id']]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Get user orders
$orders_query = "SELECT o.*, GROUP_CONCAT(CONCAT(p.name, ' (Qty: ', oi.quantity, ', Price: ', oi.price, ')') SEPARATOR '; ') as items
                 FROM orders o 
                 LEFT JOIN order_items oi ON o.id = oi.order_id 
                 LEFT JOIN products p ON oi.product_id = p.id 
                 WHERE o.user_id = ? 
                 GROUP BY o.id 
                 ORDER BY o.created_at DESC";
$orders_stmt = $db->prepare($orders_query);
$orders_stmt->execute([$_SESSION['user_id']]);
$orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for export
$export_data = [
    'account_info' => [
        'username' => $user['username'],
        'full_name' => $user['full_name'],
        'email' => $user['email'],
        'phone' => $user['phone'],
        'address' => $user['address'],
        'role' => $user['role'],
        'member_since' => $user['created_at']
    ],
    'orders' => $orders,
    'export_date' => date('Y-m-d H:i:s'),
    'total_orders' => count($orders),
    'total_spent' => array_sum(array_column($orders, 'total_amount'))
];

// Set headers for download
header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="autoparts_account_data_' . date('Y-m-d') . '.json"');
header('Cache-Control: no-cache, must-revalidate');

// Output JSON data
echo json_encode($export_data, JSON_PRETTY_PRINT);
exit();
?>
