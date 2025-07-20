<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

requireLogin();

$page_title = 'Payment Failed';

$database = new Database();
$db = $database->getConnection();

// Get order ID from query parameter
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if (!$order_id) {
    header('Location: my_orders.php');
    exit();
}

// Verify order belongs to current user
$query = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: my_orders.php');
    exit();
}

include 'includes/header.php';
?>

<main>
    <div class="container" style="padding: 2rem 20px;">
        <div style="background: white; border-radius: 10px; padding: 3rem 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center;">
            <div style="margin-bottom: 2rem;">
                <i class="fas fa-times-circle" style="font-size: 5rem; color: #dc3545;"></i>
            </div>
            
            <h1 style="margin-bottom: 1.5rem;">Payment Failed</h1>
            
            <p style="font-size: 1.2rem; margin-bottom: 2rem;">
                We couldn't process your payment for Order #<?php echo $order_id; ?>.
            </p>
            
            <div style="margin-bottom: 2rem; padding: 1rem; background: #f8f9fa; border-radius: 10px; display: inline-block;">
                <div style="font-size: 1.5rem; font-weight: bold; color: #007bff;">
                    <?php echo formatPrice($order['total_amount']); ?>
                </div>
                <div style="color: #6c757d;">
                    Order Total
                </div>
            </div>
            
            <p style="margin-bottom: 2rem;">
                Don't worry, your order is still saved. You can try again with a different payment method.
            </p>
            
            <div style="margin-top: 2rem;">
                <a href="payment.php?order_id=<?php echo $order_id; ?>" class="btn btn-primary">
                    Try Again
                </a>
                <a href="my_orders.php" class="btn btn-secondary">
                    My Orders
                </a>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
