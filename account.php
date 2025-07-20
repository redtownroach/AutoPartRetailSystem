<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

requireLogin();

$page_title = 'My Account';

$database = new Database();
$db = $database->getConnection();

// Get user details
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get user statistics
$stats_query = "SELECT 
                    COUNT(*) as total_orders,
                    COALESCE(SUM(total_amount), 0) as total_spent,
                    MAX(created_at) as last_order_date
                FROM orders WHERE user_id = ?";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute([$_SESSION['user_id']]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Get recent orders
$recent_orders_query = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
$recent_orders_stmt = $db->prepare($recent_orders_query);
$recent_orders_stmt->execute([$_SESSION['user_id']]);
$recent_orders = $recent_orders_stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<main>
    <div class="account-container">
        <div class="account-header">
            <h1>Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!</h1>
            <p>Manage your account, view orders, and update your preferences</p>
        </div>
        
        <div class="account-grid">
            <div class="account-sidebar">
                <ul class="account-nav">
                    <li><a href="account.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="edit_profile.php"><i class="fas fa-user-edit"></i> Edit Profile</a></li>
                    <li><a href="change_password.php"><i class="fas fa-key"></i> Change Password</a></li>
                    <li><a href="my_orders.php"><i class="fas fa-receipt"></i> Order History</a></li>
                    <li><a href="account_settings.php"><i class="fas fa-cog"></i> Account Settings</a></li>
                </ul>
            </div>
            
            <div class="account-content">
                <!-- Account Overview -->
                <div class="profile-section">
                    <h3>Account Overview</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem;">
                        <div style="background: var(--primary-50); padding: 1.5rem; border-radius: 10px; text-align: center;">
                            <div style="font-size: 2rem; font-weight: bold; color: var(--primary-600);">
                                <?php echo $stats['total_orders']; ?>
                            </div>
                            <div style="color: var(--gray-600);">Total Orders</div>
                        </div>
                        <div style="background: var(--success-50); padding: 1.5rem; border-radius: 10px; text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: var(--success-600);">
                                <?php echo formatPrice($stats['total_spent']); ?>
                            </div>
                            <div style="color: var(--gray-600);">Total Spent</div>
                        </div>
                        <div style="background: var(--warning-50); padding: 1.5rem; border-radius: 10px; text-align: center;">
                            <div style="font-size: 1rem; font-weight: bold; color: var(--warning-600);">
                                <?php echo $stats['last_order_date'] ? date('M j, Y', strtotime($stats['last_order_date'])) : 'No orders yet'; ?>
                            </div>
                            <div style="color: var(--gray-600);">Last Order</div>
                        </div>
                    </div>
                </div>
                
                <!-- Personal Information -->
                <div class="profile-section">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <h3>Personal Information</h3>
                        <a href="edit_profile.php" class="btn btn-outline btn-sm">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    </div>
                    <div class="profile-info">
                        <div class="info-item">
                            <div class="info-label">Full Name</div>
                            <div class="info-value"><?php echo htmlspecialchars($user['full_name']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Username</div>
                            <div class="info-value"><?php echo htmlspecialchars($user['username']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Email Address</div>
                            <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Phone Number</div>
                            <div class="info-value"><?php echo htmlspecialchars($user['phone'] ?: 'Not provided'); ?></div>
                        </div>
                        <div class="info-item" style="grid-column: 1 / -1;">
                            <div class="info-label">Address</div>
                            <div class="info-value"><?php echo $user['address'] ? nl2br(htmlspecialchars($user['address'])) : 'Not provided'; ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Orders -->
                <div class="profile-section">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <h3>Recent Orders</h3>
                        <a href="my_orders.php" class="btn btn-outline btn-sm">
                            <i class="fas fa-eye"></i> View All
                        </a>
                    </div>
                    <?php if (empty($recent_orders)): ?>
                        <div style="text-align: center; padding: 2rem; color: var(--gray-500);">
                            <i class="fas fa-shopping-cart" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                            <p>No orders yet. <a href="products.php">Start shopping!</a></p>
                        </div>
                    <?php else: ?>
                        <div class="data-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Date</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_orders as $order): ?>
                                        <tr>
                                            <td>#<?php echo $order['id']; ?></td>
                                            <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                            <td><?php echo formatPrice($order['total_amount']); ?></td>
                                            <td>
                                                <span style="background: <?php 
                                                    echo $order['status'] === 'pending' ? '#ffc107' : 
                                                        ($order['status'] === 'delivered' ? '#28a745' : 
                                                        ($order['status'] === 'cancelled' ? '#dc3545' : '#007bff')); 
                                                ?>; color: white; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.8rem;">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">View</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
