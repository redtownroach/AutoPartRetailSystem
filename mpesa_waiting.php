<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

requireLogin();

$page_title = 'M-Pesa Payment';

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

// Get payment status
$payment_query = "SELECT * FROM payments WHERE order_id = ? ORDER BY id DESC LIMIT 1";
$payment_stmt = $db->prepare($payment_query);
$payment_stmt->execute([$order_id]);
$payment = $payment_stmt->fetch(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<main>
    <div class="container" style="padding: 2rem 20px;">
        <h1>M-Pesa Payment</h1>
        
        <div style="background: white; border-radius: 10px; padding: 3rem 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center;">
            <div style="margin-bottom: 2rem;">
                <i class="fas fa-mobile-alt" style="font-size: 4rem; color: #28a745;"></i>
            </div>
            
            <h2 style="margin-bottom: 1.5rem;">Check Your Phone</h2>
            
            <p style="font-size: 1.2rem; margin-bottom: 2rem;">
                We've sent an M-Pesa payment request to your phone. Please check your phone and enter your M-Pesa PIN to complete the payment.
            </p>
            
            <div style="margin-bottom: 2rem;">
                <div class="spinner" style="display: inline-block; width: 3rem; height: 3rem; border: 0.25rem solid #f3f3f3; border-top: 0.25rem solid #007bff; border-radius: 50%; animation: spin 1s linear infinite;"></div>
            </div>
            
            <p style="color: #6c757d; margin-bottom: 2rem;">
                This page will automatically refresh to check the payment status. Please do not close this page.
            </p>
            
            <div style="margin-top: 2rem;">
                <a href="order_details.php?id=<?php echo $order_id; ?>" class="btn btn-secondary">
                    Check Order Status
                </a>
                <a href="payment.php?order_id=<?php echo $order_id; ?>" class="btn btn-primary">
                    Try Another Payment Method
                </a>
            </div>
        </div>
    </div>
</main>

<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<script>
    // Check payment status every 5 seconds
    setInterval(function() {
        fetch('check_payment_status.php?order_id=<?php echo $order_id; ?>')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'completed') {
                    window.location.href = 'payment_success.php?order_id=<?php echo $order_id; ?>';
                } else if (data.status === 'failed') {
                    window.location.href = 'payment_failed.php?order_id=<?php echo $order_id; ?>';
                }
            });
    }, 5000);
</script>

<?php include 'includes/footer.php'; ?>
