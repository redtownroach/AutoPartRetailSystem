<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

requireLogin();

$page_title = 'Edit Profile';

$database = new Database();
$db = $database->getConnection();

$errors = [];
$success = false;

// Get current user data
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize_input($_POST['full_name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $address = sanitize_input($_POST['address']);
    
    if (empty($full_name)) {
        $errors[] = 'Full name is required';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    if (empty($errors)) {
        // Check if email is already taken by another user
        $check_query = "SELECT id FROM users WHERE email = ? AND id != ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute([$email, $_SESSION['user_id']]);
        
        if ($check_stmt->fetch()) {
            $errors[] = 'Email address is already in use by another account';
        } else {
            $update_query = "UPDATE users SET full_name = ?, email = ?, phone = ?, address = ? WHERE id = ?";
            $update_stmt = $db->prepare($update_query);
            
            if ($update_stmt->execute([$full_name, $email, $phone, $address, $_SESSION['user_id']])) {
                $success = true;
                // Refresh user data
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $errors[] = 'Error updating profile. Please try again.';
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
                    <li><a href="edit_profile.php" class="active"><i class="fas fa-user-edit"></i> Edit Profile</a></li>
                    <li><a href="change_password.php"><i class="fas fa-key"></i> Change Password</a></li>
                    <li><a href="my_orders.php"><i class="fas fa-receipt"></i> Order History</a></li>
                    <li><a href="account_settings.php"><i class="fas fa-cog"></i> Account Settings</a></li>
                </ul>
            </div>
            
            <div class="account-content">
                <div style="margin-bottom: 2rem;">
                    <h2>Edit Profile</h2>
                    <p style="color: var(--gray-600);">Update your personal information and contact details</p>
                </div>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> Profile updated successfully!
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
                
                <form method="POST">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                        <div class="form-group">
                            <label for="full_name">Full Name *</label>
                            <input type="text" id="full_name" name="full_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['phone'] ?: ''); ?>" 
                                   placeholder="+255 123 456 789">
                        </div>
                        
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" 
                                   disabled style="background: var(--gray-100); color: var(--gray-500);">
                            <small style="color: var(--gray-500);">Username cannot be changed</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" class="form-control" rows="4" 
                                  placeholder="Enter your full address including street, city, and region"><?php echo htmlspecialchars($user['address'] ?: ''); ?></textarea>
                    </div>
                    
                    <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <a href="account.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
