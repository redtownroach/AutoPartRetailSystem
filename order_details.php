<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

requireLogin();

$page_title = 'Order Details';

$database = new Database();
$db = $database->getConnection();

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$order_id) {
    header('Location: my_orders.php');
    exit();
}

// Get order details (only for current user)
$query = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: my_orders.php');
    exit();
}

// Get order items
$items_query = "SELECT oi.*, p.name, p.part_number, p.image_url
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = ?";
$items_stmt = $db->prepare($items_query);
$items_stmt->execute([$order_id]);
$order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<main>
    <div class="container" style="padding: 2rem 20px;">
        <div style="margin-bottom: 2rem;">
            <a href="my_orders.php" style="color: #007bff; text-decoration: none;">
                <i class="fas fa-arrow-left"></i> Back to My Orders
            </a>
        </div>
        
        <h1>Order Details #<?php echo $order['id']; ?></h1>
        
        <!-- Order Status Timeline -->
        <div style="background: white; border-radius: 10px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h3 style="margin-bottom: 1.5rem;">Order Status</h3>
            <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                <?php
                $statuses = ['pending', 'processing', 'shipped', 'delivered'];
                $current_status = $order['status'];
                $current_index = array_search($current_status, $statuses);
                
                foreach ($statuses as $index => $status):
                    $is_active = $index <= $current_index && $current_status !== 'cancelled';
                    $is_current = $status === $current_status;
                ?>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <div style="width: 30px; height: 30px; border-radius: 50%; background: <?php echo $is_active ? '#007bff' : '#e9ecef'; ?>; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                            <?php echo $index + 1; ?>
                        </div>
                        <span style="color: <?php echo $is_active ? '#007bff' : '#6c757d'; ?>; font-weight: <?php echo $is_current ? 'bold' : 'normal'; ?>;">
                            <?php echo ucfirst($status); ?>
                        </span>
                        <?php if ($index < count($statuses) - 1): ?>
                            <div style="width: 50px; height: 2px; background: <?php echo $index < $current_index ? '#007bff' : '#e9ecef'; ?>; margin: 0 1rem;"></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                
                <?php if ($current_status === 'cancelled'): ?>
                    <div style="margin-left: 2rem;">
                        <span style="background: #dc3545; color: white; padding: 0.5rem 1rem; border-radius: 5px; font-weight: bold;">
                            CANCELLED
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
            <!-- Order Items -->
            <div style="background: white; border-radius: 10px; padding: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h3 style="margin-bottom: 1.5rem;">Order Items</h3>
                <div style="display: grid; gap: 1rem;">
                    <?php foreach ($order_items as $item): ?>
                        <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; border: 1px solid #eee; border-radius: 5px;">
                            <div style="width: 80px; height: 80px; background: #f8f9fa; border-radius: 5px; display: flex; align-items: center; justify-content: center;">
                                <?php if ($item['image_url']): ?>
                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>"
                                         style="max-width: 100%; max-height: 100%; border-radius: 3px;">
                                <?php else: ?>
                                    <i class="fas fa-cog" style="font-size: 2rem; color: #6c757d;"></i>
                                <?php endif; ?>
                            </div>
                            <div style="flex: 1;">
                                <h4 style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars($item['name']); ?></h4>
                                <p style="color: #6c757d; margin-bottom: 0.5rem;">Part #: <?php echo htmlspecialchars($item['part_number']); ?></p>
                                <p style="color: #6c757d;">Quantity: <?php echo $item['quantity']; ?></p>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 1.1rem; font-weight: bold; color: #007bff;">
                                    <?php echo formatPrice($item['price'] * $item['quantity']); ?>
                                </div>
                                <div style="color: #6c757d; font-size: 0.9rem;">
                                    <?php echo formatPrice($item['price']); ?> each
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Order Summary -->
            <div>
                <!-- Order Information -->
                <div style="background: white; border-radius: 10px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <h3 style="margin-bottom: 1.5rem;">Order Information</h3>
                    <div style="margin-bottom: 1rem;">
                        <strong>Order Date:</strong><br>
                        <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <strong>Phone:</strong><br>
                        <?php echo htmlspecialchars($order['phone']); ?>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <strong>Shipping Address:</strong><br>
                        <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                    </div>
                </div>
                
                <!-- Order Total -->
                <div style="background: white; border-radius: 10px; padding: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <h3 style="margin-bottom: 1.5rem;">Order Total</h3>
                    <div style="border-top: 2px solid #007bff; padding-top: 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <strong style="font-size: 1.2rem;">Total:</strong>
                            <strong style="font-size: 1.5rem; color: #007bff;"><?php echo formatPrice($order['total_amount']); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
@media (max-width: 768px) {
    .container > div:nth-child(4) {
        grid-template-columns: 1fr !important;
        gap: 1rem !important;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
