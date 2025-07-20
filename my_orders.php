<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

requireLogin();

$page_title = 'My Orders';

$database = new Database();
$db = $database->getConnection();

// Get user's orders
$query = "SELECT o.*, COUNT(oi.id) as item_count
          FROM orders o 
          LEFT JOIN order_items oi ON o.id = oi.order_id
          WHERE o.user_id = ? 
          GROUP BY o.id
          ORDER BY o.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<main>
    <div class="container" style="padding: 2rem 20px;">
        <h1>My Orders</h1>
        
        <?php if (empty($orders)): ?>
            <div style="text-align: center; padding: 3rem; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <i class="fas fa-shopping-bag" style="font-size: 4rem; color: #6c757d; margin-bottom: 1rem;"></i>
                <h3 style="color: #6c757d; margin-bottom: 1rem;">No Orders Yet</h3>
                <p style="color: #6c757d; margin-bottom: 2rem;">You haven't placed any orders yet. Start shopping to see your orders here!</p>
                <a href="products.php" class="btn btn-primary">Start Shopping</a>
            </div>
        <?php else: ?>
            <div style="display: grid; gap: 1.5rem;">
                <?php foreach ($orders as $order): ?>
                    <div style="background: white; border-radius: 10px; padding: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                            <div>
                                <h3 style="margin-bottom: 0.5rem;">Order #<?php echo $order['id']; ?></h3>
                                <p style="color: #6c757d; margin-bottom: 0.5rem;">
                                    Placed on <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?>
                                </p>
                                <p style="color: #6c757d;">
                                    <?php echo $order['item_count']; ?> item<?php echo $order['item_count'] != 1 ? 's' : ''; ?>
                                </p>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 1.5rem; font-weight: bold; color: #007bff; margin-bottom: 0.5rem;">
                                    <?php echo formatPrice($order['total_amount']); ?>
                                </div>
                                <span style="background: <?php 
                                    echo $order['status'] === 'pending' ? '#ffc107' : 
                                        ($order['status'] === 'delivered' ? '#28a745' : 
                                        ($order['status'] === 'cancelled' ? '#dc3545' : '#007bff')); 
                                ?>; color: white; padding: 0.5rem 1rem; border-radius: 5px; font-size: 0.9rem;">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div style="border-top: 1px solid #eee; padding-top: 1rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <strong>Shipping Address:</strong><br>
                                    <span style="color: #6c757d;"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></span>
                                </div>
                                <div style="display: flex; gap: 1rem; align-items: center;">
                            <?php if ($order['status'] === 'pending'): ?>
                                <a href="payment.php?order_id=<?php echo $order['id']; ?>" class="btn btn-success">
                                    <i class="fas fa-credit-card"></i> Pay Now
                                </a>
                            <?php endif; ?>
                            <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-primary">
                                View Details
                            </a>
                        </div>
                            </div>
                        </div>
                        
                        <?php if ($order['status'] === 'pending'): ?>
                    <div style="margin-top: 1rem; padding: 1rem; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-exclamation-triangle" style="color: #856404;"></i>
                            <strong style="color: #856404;">Payment Required</strong>
                        </div>
                        <p style="color: #856404; margin: 0.5rem 0 0 0; font-size: 0.9rem;">
                            This order is awaiting payment. Click "Pay Now" to complete your purchase using credit card or M-Pesa.
                        </p>
                    </div>
                <?php elseif ($order['status'] === 'processing'): ?>
                    <div style="margin-top: 1rem; padding: 1rem; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 5px;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-cog fa-spin" style="color: #0c5460;"></i>
                            <strong style="color: #0c5460;">Order Processing</strong>
                        </div>
                        <p style="color: #0c5460; margin: 0.5rem 0 0 0; font-size: 0.9rem;">
                            Payment received! We're preparing your order for shipment.
                        </p>
                    </div>
                <?php elseif ($order['status'] === 'shipped'): ?>
                    <div style="margin-top: 1rem; padding: 1rem; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-truck" style="color: #155724;"></i>
                            <strong style="color: #155724;">Order Shipped</strong>
                        </div>
                        <p style="color: #155724; margin: 0.5rem 0 0 0; font-size: 0.9rem;">
                            Your order is on its way! You should receive it soon.
                        </p>
                    </div>
                <?php elseif ($order['status'] === 'delivered'): ?>
                    <div style="margin-top: 1rem; padding: 1rem; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-check-circle" style="color: #155724;"></i>
                            <strong style="color: #155724;">Order Delivered</strong>
                        </div>
                        <p style="color: #155724; margin: 0.5rem 0 0 0; font-size: 0.9rem;">
                            Your order has been successfully delivered. Thank you for shopping with us!
                        </p>
                    </div>
                <?php elseif ($order['status'] === 'cancelled'): ?>
                    <div style="margin-top: 1rem; padding: 1rem; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-times-circle" style="color: #721c24;"></i>
                            <strong style="color: #721c24;">Order Cancelled</strong>
                        </div>
                        <p style="color: #721c24; margin: 0.5rem 0 0 0; font-size: 0.9rem;">
                            This order has been cancelled. If you have any questions, please contact our support team.
                        </p>
                    </div>
                <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
