<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

requireLogin();

$page_title = 'Checkout';

$database = new Database();
$db = $database->getConnection();

// Check if cart is empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit();
}

$cart_items = [];
$cart_total = 0;

$product_ids = array_keys($_SESSION['cart']);
$placeholders = str_repeat('?,', count($product_ids) - 1) . '?';

$query = "SELECT * FROM products WHERE id IN ($placeholders)";
$stmt = $db->prepare($query);
$stmt->execute($product_ids);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($products as $product) {
    $quantity = $_SESSION['cart'][$product['id']];
    $subtotal = $product['price'] * $quantity;
    $cart_total += $subtotal;
    
    $cart_items[] = [
        'product' => $product,
        'quantity' => $quantity,
        'subtotal' => $subtotal
    ];
}

// Get user info
$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = $db->prepare($user_query);
$user_stmt->execute([$_SESSION['user_id']]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

$errors = [];
$success = false;
$order_id = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipping_address = sanitize_input($_POST['shipping_address']);
    $phone = sanitize_input($_POST['phone']);
    
    if (empty($shipping_address)) {
        $errors[] = 'Shipping address is required';
    }
    
    if (empty($phone)) {
        $errors[] = 'Phone number is required';
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Create order
            $order_query = "INSERT INTO orders (user_id, total_amount, shipping_address, phone) VALUES (?, ?, ?, ?)";
            $order_stmt = $db->prepare($order_query);
            $order_stmt->execute([$_SESSION['user_id'], $cart_total, $shipping_address, $phone]);
            $order_id = $db->lastInsertId();
            
            // Add order items
            $item_query = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
            $item_stmt = $db->prepare($item_query);
            
            foreach ($cart_items as $item) {
                $item_stmt->execute([
                    $order_id,
                    $item['product']['id'],
                    $item['quantity'],
                    $item['product']['price']
                ]);
                
                // Update stock
                $stock_query = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?";
                $stock_stmt = $db->prepare($stock_query);
                $stock_stmt->execute([$item['quantity'], $item['product']['id']]);
            }
            
            $db->commit();
            
            // Clear cart
            unset($_SESSION['cart']);
            
            // Redirect to payment page immediately
            header('Location: payment.php?order_id=' . $order_id);
            exit();
            
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'Error processing order. Please try again.';
        }
    }
}

include 'includes/header.php';
?>

<main>
    <div class="container" style="padding: 2rem 20px;">
        <h1>Checkout</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <h3>Order Placed Successfully!</h3>
                <p>Thank you for your order. Please proceed to payment to complete your purchase.</p>
                <a href="payment.php?order_id=<?php echo $order_id; ?>" class="btn btn-primary">Proceed to Payment</a>
            </div>
        <?php else: ?>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul style="margin: 0; padding-left: 1rem;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem;">
                <!-- Order Summary -->
                <div>
                    <h3>Order Summary</h3>
                    <div style="background: white; border-radius: 10px; padding: 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <?php foreach ($cart_items as $item): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem 0; border-bottom: 1px solid #eee;">
                                <div>
                                    <strong><?php echo htmlspecialchars($item['product']['name']); ?></strong><br>
                                    <small>Qty: <?php echo $item['quantity']; ?></small>
                                </div>
                                <div>
                                    <?php echo formatPrice($item['subtotal']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem 0; font-size: 1.2rem; font-weight: bold;">
                            <div>Total:</div>
                            <div><?php echo formatPrice($cart_total); ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Shipping Information -->
                <div>
                    <h3>Shipping Information</h3>
                    <div style="background: white; border-radius: 10px; padding: 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <form method="POST">
                            <div class="form-group">
                                <label>Full Name</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" readonly>
                            </div>
                            
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                            </div>
                            
                            <div class="form-group">
                                <label>Phone Number *</label>
                                <input type="tel" name="phone" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                                <small class="form-text text-muted">This will be used for delivery coordination and payment notifications</small>
                            </div>
                            
                            <div class="form-group">
                                <label>Shipping Address *</label>
                                <textarea name="shipping_address" class="form-control" rows="4" required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>
                            
                            <div style="margin-top: 1.5rem;">
                                <p style="color: #6c757d; margin-bottom: 1rem;">
                                    By placing your order, you agree to our terms and conditions. After submitting, you'll be directed to our secure payment page.
                                </p>
                            </div>
                            
                            <button type="submit" class="btn btn-primary" style="width: 100%;">
                                Place Order & Proceed to Payment
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<style>
@media (max-width: 768px) {
    .container > div:nth-child(3) {
        grid-template-columns: 1fr !important;
        gap: 2rem !important;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
