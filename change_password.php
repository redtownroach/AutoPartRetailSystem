<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

requireLogin();

$page_title = 'Change Password';

$database = new Database();
$db = $database->getConnection();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password)) {
        $errors[] = 'Current password is required';
    }
    
    if (empty($new_password)) {
        $errors[] = 'New password is required';
    }
    
    if (strlen($new_password) < 6) {
        $errors[] = 'New password must be at least 6 characters long';
    }
    
    if ($new_password !== $confirm_password) {
        $errors[] = 'New passwords do not match';
    }
    
    if (empty($errors)) {
        // Verify current password
        $query = "SELECT password FROM users WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!password_verify($current_password, $user['password'])) {
            $errors[] = 'Current password is incorrect';
        } else {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET password = ? WHERE id = ?";
            $update_stmt = $db->prepare($update_query);
            
            if ($update_stmt->execute([$hashed_password, $_SESSION['user_id']])) {
                $success = true;
            } else {
                $errors[] = 'Error updating password. Please try again.';
            }
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
                    <li><a href="change_password.php" class="active"><i class="fas fa-key"></i> Change Password</a></li>
                    <li><a href="my_orders.php"><i class="fas fa-receipt"></i> Order History</a></li>
                    <li><a href="account_settings.php"><i class="fas fa-cog"></i> Account Settings</a></li>
                </ul>
            </div>
            
            <div class="account-content">
                <div style="margin-bottom: 2rem;">
                    <h2>Change Password</h2>
                    <p style="color: var(--gray-600);">Update your account password for better security</p>
                </div>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> Password changed successfully!
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul style="margin: 0; padding-left: 1rem;">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <div style="max-width: 500px;">
                    <form method="POST">
                        <div class="form-group">
                            <label for="current_password">Current Password *</label>
                            <input type="password" id="current_password" name="current_password" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password *</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" 
                                   minlength="6" required>
                            <small style="color: var(--gray-500);">Password must be at least 6 characters long</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password *</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                                   minlength="6" required>
                        </div>
                        
                        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                            <a href="account.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
                
                <!-- Security Tips -->
                <div style="background: var(--info-50); border: 1px solid var(--info-200); border-radius: 10px; padding: 1.5rem; margin-top: 2rem;">
                    <h4 style="color: var(--info-700); margin-bottom: 1rem;">
                        <i class="fas fa-shield-alt"></i> Password Security Tips
                    </h4>
                    <ul style="color: var(--info-700); margin: 0; padding-left: 1.5rem;">
                        <li>Use a combination of uppercase and lowercase letters</li>
                        <li>Include numbers and special characters</li>
                        <li>Make it at least 8 characters long</li>
                        <li>Don't use personal information like your name or birthday</li>
                        <li>Don't reuse passwords from other accounts</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
