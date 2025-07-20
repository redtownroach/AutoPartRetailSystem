<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : '';
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Verify product exists and is in stock
$query = "SELECT stock_quantity FROM products WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit();
}

switch ($action) {
    case 'add':
        if ($product['stock_quantity'] < $quantity) {
            echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
            exit();
        }
        addToCart($product_id, $quantity);
        echo json_encode(['success' => true, 'cart_count' => getCartItemCount()]);
        break;
        
    case 'update':
        if ($quantity < 1) {
            echo json_encode(['success' => false, 'message' => 'Invalid quantity']);
            exit();
        }
        if ($product['stock_quantity'] < $quantity) {
            echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
            exit();
        }
        $_SESSION['cart'][$product_id] = $quantity;
        echo json_encode(['success' => true, 'cart_count' => getCartItemCount()]);
        break;
        
    case 'remove':
        removeFromCart($product_id);
        echo json_encode(['success' => true, 'cart_count' => getCartItemCount()]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
?>
