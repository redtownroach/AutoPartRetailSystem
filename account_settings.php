<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

requireLogin();

$page_title = 'Account Settings';

$database = new Database();
$db = $database->getConnection();

$errors = [];
$success = false;

// Get user data
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle account deletion request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
    $password = $_POST['delete_password'];
    
    if (empty($password)) {
        $errors[] = 'Password is required to delete account';
    } else {
        if (password_verify($password, $user['password'])) {
            // Check if user has pending orders
            $orders_query = "SELECT COUNT(*) FROM orders WHERE user_id = ? AND status IN ('pending', 'processing')";
            $orders_stmt = $db->prepare($orders_query);
            $orders_stmt->execute([$_SESSION['user_id']]);
            $pending_orders = $orders_stmt->fetchColumn();
            
            if ($pending_orders > 0) {
                $errors[] = "Cannot delete account. You have $pending_orders pending orders. Please wait for them to be completed or contact support.";
            } else {
                // Delete user account (orders will remain for record keeping)
                $delete_query = "DELETE FROM users WHERE id = ?";
                $delete_stmt = $db->prepare($delete_query);
                
                if ($delete_stmt->execute([$_SESSION['user_id']])) {
                    session_destroy();
                    header('Location: index.php?message=account_deleted');
                    exit();
                } else {
                    $errors[] = 'Error deleting account. Please try again or contact support.';
                }
            }
        } else {
            $errors[] = 'Incorrect password';
        }
    }
}

include 'includes/header.php';
?>

<main>
    <div class="account-container">
        <div class="account-grid">
            <div class="account-sidebar">
                <ul class="account-nav">
                    <li><a href="account.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="edit_profile.php"><i class="fas fa-user-edit"></i> Edit Profile</a></li>
                    <li><a href="change_password.php"><i class="fas fa-key"></i> Change Password</a></li>
                    <li><a href="my_orders.php"><i class="fas fa-receipt"></i> Order History</a></li>
                    <li><a href="account_settings.php" class="active"><i class="fas fa-cog"></i> Account Settings</a></li>
                </ul>
            </div>
            
            <div class="account-content">
                <div style="margin-bottom: 2rem;">
                    <h2>Account Settings</h2>
                    <p style="color: var(--gray-600);">Manage your account preferences and data</p>
                </div>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul style="margin: 0; padding-left: 1rem;">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <!-- Account Information -->
                <div style="background: var(--gray-50); border-radius: 10px; padding: 2rem; margin-bottom: 2rem;">
                    <h3 style="margin-bottom: 1rem;">Account Information</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                        <div>
                            <strong>Account Type:</strong><br>
                            <span style="background: <?php echo $user['role'] === 'admin' ? '#007bff' : '#28a745'; ?>; color: white; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.9rem;">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </div>
                        <div>
                            <strong>Member Since:</strong><br>
                            <?php echo date('F j, Y', strtotime($user['created_at'])); ?>
                        </div>
                        <div>
                            <strong>Last Login:</strong><br>
                            Today
                        </div>
                    </div>
                </div>
                
                <!-- Data Export -->
                <div style="background: white; border: 1px solid var(--gray-200); border-radius: 10px; padding: 2rem; margin-bottom: 2rem;">
                    <h3 style="margin-bottom: 1rem;">
                        <i class="fas fa-download"></i> Export Your Data
                    </h3>
                    <p style="color: var(--gray-600); margin-bottom: 1rem;">
                        Download a copy of your account data including profile information and order history.
                    </p>
                    <a href="export_data.php" class="btn btn-outline">
                        <i class="fas fa-file-download"></i> Download Data
                    </a>
                </div>
                
                <!-- Privacy Settings -->
                <div style="background: white; border: 1px solid var(--gray-200); border-radius: 10px; padding: 2rem; margin-bottom: 2rem;">
                    <h3 style="margin-bottom: 1rem;">
                        <i class="fas fa-shield-alt"></i> Privacy & Security
                    </h3>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" checked disabled>
                            <span>Receive order updates via email</span>
                        </label>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" checked disabled>
                            <span>Receive promotional emails</span>
                        </label>
                    </div>
                    <p style="color: var(--gray-500); font-size: 0.9rem;">
                        Email preferences can be managed by contacting support.
                    </p>
                </div>
                
                <!-- Danger Zone -->
                <div style="background: var(--danger-50); border: 1px solid var(--danger-200); border-radius: 10px; padding: 2rem;">
                    <h3 style="color: var(--danger-700); margin-bottom: 1rem;">
                        <i class="fas fa-exclamation-triangle"></i> Danger Zone
                    </h3>
                    <p style="color: var(--danger-700); margin-bottom: 1rem;">
                        Once you delete your account, there is no going back. Please be certain.
                    </p>
                    
                    <button type="button" class="btn btn-danger" onclick="showDeleteModal()">
                        <i class="fas fa-trash"></i> Delete Account
                    </button>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Delete Account Modal -->
<div id="deleteModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 15px; padding: 2rem; max-width: 500px; width: 90%;">
        <h3 style="color: var(--danger-600); margin-bottom: 1rem;">
            <i class="fas fa-exclamation-triangle"></i> Delete Account
        </h3>
        <p style="margin-bottom: 1.5rem;">
            This action cannot be undone. This will permanently delete your account and remove your data from our servers.
        </p>
        
        <form method="POST">
            <div class="form-group">
                <label for="delete_password">Enter your password to confirm:</label>
                <input type="password" id="delete_password" name="delete_password" class="form-control" required>
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="hideDeleteModal()">Cancel</button>
                <button type="submit" name="delete_account" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Delete Account
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showDeleteModal() {
    document.getElementById('deleteModal').style.display = 'flex';
}

function hideDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideDeleteModal();
    }
});
</script>

<?php include 'includes/footer.php'; ?>
