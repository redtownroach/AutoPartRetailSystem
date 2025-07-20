<?php
require_once 'includes/functions.php';
require_once 'config/database.php';
require_once 'includes/payment_gateway.php';

requireLogin();

$page_title = 'Payment';

$database = new Database();
$db = $database->getConnection();

// Get order ID from session or query parameter
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if (!$order_id) {
    header('Location: my_orders.php');
    exit();
}

// Verify order belongs to current user
$query = "SELECT * FROM orders WHERE id = ? AND user_id = ? AND status = 'pending'";
$stmt = $db->prepare($query);
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: my_orders.php');
    exit();
}

// Get user info
$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = $db->prepare($user_query);
$user_stmt->execute([$_SESSION['user_id']]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Initialize payment gateway
$payment_gateway = new PaymentGateway($db);

// Process payment form submission
$payment_result = null;
$payment_error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = sanitize_input($_POST['payment_method']);
    
    // Initialize payment
    $customer_info = [
        'name' => $user['full_name'],
        'email' => $user['email'],
        'phone' => $user['phone']
    ];
    
    $payment_id = $payment_gateway->initializePayment($order_id, $order['total_amount'], $customer_info);
    
    if ($payment_method === 'credit_card') {
        // Process credit card payment
        if (isset($_POST['stripeToken'])) {
            $token = $_POST['stripeToken'];
            $payment_result = $payment_gateway->processCreditCardPayment($token);
            
            if ($payment_result['success']) {
                // Redirect to success page
                header('Location: payment_success.php?order_id=' . $order_id);
                exit();
            } else {
                $payment_error = $payment_result['message'];
            }
        } else {
            $payment_error = 'Invalid payment token';
        }
    } elseif ($payment_method === 'mpesa') {
        // Process M-Pesa payment
        $phone_number = sanitize_input($_POST['phone_number']);
        $payment_result = $payment_gateway->processMpesaPayment($phone_number);
        
        if ($payment_result['success']) {
            // Store checkout request ID in session for verification
            $_SESSION['mpesa_checkout_request_id'] = $payment_result['checkout_request_id'];
            $_SESSION['mpesa_order_id'] = $order_id;
            
            // Redirect to waiting page
            header('Location: mpesa_waiting.php?order_id=' . $order_id);
            exit();
        } else {
            $payment_error = $payment_result['message'];
        }
    } else {
        $payment_error = 'Invalid payment method';
    }
}

include 'includes/header.php';
?>

<main>
    <div class="container" style="padding: 2rem 20px;">
        <div style="margin-bottom: 2rem;">
            <a href="checkout.php" style="color: #007bff; text-decoration: none;">
                <i class="fas fa-arrow-left"></i> Back to Checkout
            </a>
        </div>
        
        <h1>Payment for Order #<?php echo $order_id; ?></h1>
        
        <?php if ($payment_error): ?>
            <div class="alert alert-danger">
                <?php echo $payment_error; ?>
            </div>
        <?php endif; ?>
        
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
            <!-- Payment Methods -->
            <div>
                <div style="background: white; border-radius: 10px; padding: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <h3 style="margin-bottom: 1.5rem;">Select Payment Method</h3>
                    
                    <div style="margin-bottom: 2rem;">
                        <ul class="nav nav-tabs" id="paymentTabs" role="tablist" style="display: flex; border-bottom: 1px solid #dee2e6; margin-bottom: 1rem;">
                            <li style="margin-right: 0.5rem;">
                                <a class="active" id="credit-card-tab" data-toggle="tab" href="#credit-card" role="tab" 
                                   style="display: block; padding: 0.5rem 1rem; border: 1px solid #dee2e6; border-bottom: none; border-radius: 0.25rem 0.25rem 0 0; background-color: #fff; color: #007bff; text-decoration: none;"
                                   onclick="activateTab('credit-card')">
                                    <i class="fas fa-credit-card"></i> Credit Card
                                </a>
                            </li>
                            <li>
                                <a id="mpesa-tab" data-toggle="tab" href="#mpesa" role="tab"
                                   style="display: block; padding: 0.5rem 1rem; border: 1px solid #dee2e6; border-bottom: none; border-radius: 0.25rem 0.25rem 0 0; background-color: #f8f9fa; color: #495057; text-decoration: none;"
                                   onclick="activateTab('mpesa')">
                                    <i class="fas fa-mobile-alt"></i> M-Pesa
                                </a>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="paymentTabsContent">
                            <!-- Credit Card Payment Form -->
                            <div class="tab-pane fade show active" id="credit-card" role="tabpanel">
                                <form action="payment.php?order_id=<?php echo $order_id; ?>" method="POST" id="payment-form">
                                    <input type="hidden" name="payment_method" value="credit_card">
                                    
                                    <div class="form-group">
                                        <label for="card-element">Credit or Debit Card</label>
                                        <div id="card-element" style="padding: 1rem; border: 1px solid #ced4da; border-radius: 0.25rem; background-color: #fff;">
                                            <!-- Stripe Elements will be inserted here -->
                                        </div>
                                        <div id="card-errors" role="alert" style="color: #dc3545; margin-top: 0.5rem;"></div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">
                                        Pay <?php echo formatPrice($order['total_amount']); ?>
                                    </button>
                                </form>
                            </div>
                            
                            <!-- M-Pesa Payment Form -->
                            <div class="tab-pane fade" id="mpesa" role="tabpanel">
                                <form action="payment.php?order_id=<?php echo $order_id; ?>" method="POST">
                                    <input type="hidden" name="payment_method" value="mpesa">
                                    
                                    <div class="form-group">
                                        <label for="phone_number">M-Pesa Phone Number</label>
                                        <input type="text" id="phone_number" name="phone_number" class="form-control" 
                                               placeholder="e.g., 0712345678" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                                        <small class="form-text text-muted">Enter the phone number registered with M-Pesa</small>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-success" style="margin-top: 1rem;">
                                        Pay with M-Pesa <?php echo formatPrice($order['total_amount']); ?>
                                    </button>
                                </form>
                                
                                <div style="margin-top: 2rem; padding: 1rem; background-color: #f8f9fa; border-radius: 0.25rem;">
                                    <h5>How to Pay with M-Pesa:</h5>
                                    <ol style="padding-left: 1.5rem;">
                                        <li>Enter your M-Pesa registered phone number</li>
                                        <li>Click "Pay with M-Pesa"</li>
                                        <li>You will receive a prompt on your phone</li>
                                        <li>Enter your M-Pesa PIN to complete the payment</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Order Summary -->
            <div>
                <div style="background: white; border-radius: 10px; padding: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <h3 style="margin-bottom: 1.5rem;">Order Summary</h3>
                    
                    <div style="margin-bottom: 1rem;">
                        <strong>Order ID:</strong> #<?php echo $order_id; ?>
                    </div>
                    
                    <div style="margin-bottom: 1rem;">
                        <strong>Total Amount:</strong> <?php echo formatPrice($order['total_amount']); ?>
                    </div>
                    
                    <div style="margin-bottom: 1rem;">
                        <strong>Shipping Address:</strong><br>
                        <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                    </div>
                    
                    <div style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #dee2e6;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <strong>Total:</strong>
                            <strong style="font-size: 1.25rem; color: #007bff;"><?php echo formatPrice($order['total_amount']); ?></strong>
                        </div>
                    </div>
                </div>
                
                <div style="margin-top: 2rem; background: #f8f9fa; border-radius: 10px; padding: 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <h5 style="margin-bottom: 1rem;">Secure Payment</h5>
                    <p style="color: #6c757d; margin-bottom: 1rem;">
                        All payments are secure and encrypted. We never store your credit card information.
                    </p>
                    <div style="display: flex; gap: 1rem;">
                        <i class="fas fa-lock" style="color: #28a745; font-size: 1.5rem;"></i>
                        <div style="display: flex; gap: 0.5rem;">
                            <i class="fab fa-cc-visa" style="font-size: 1.5rem; color: #1a1f71;"></i>
                            <i class="fab fa-cc-mastercard" style="font-size: 1.5rem; color: #eb001b;"></i>
                            <i class="fab fa-cc-amex" style="font-size: 1.5rem; color: #2e77bc;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Include Stripe.js -->
<script src="https://js.stripe.com/v3/"></script>
<script>
    // Create a Stripe client
    var stripe = Stripe('<?php echo $payment_gateway->getStripePublishableKey(); ?>');
    var elements = stripe.elements();
    
    // Create an instance of the card Element
    var card = elements.create('card', {
        style: {
            base: {
                fontSize: '16px',
                color: '#32325d',
            }
        }
    });
    
    // Add an instance of the card Element into the `card-element` div
    card.mount('#card-element');
    
    // Handle real-time validation errors from the card Element
    card.addEventListener('change', function(event) {
        var displayError = document.getElementById('card-errors');
        if (event.error) {
            displayError.textContent = event.error.message;
        } else {
            displayError.textContent = '';
        }
    });
    
    // Handle form submission
    var form = document.getElementById('payment-form');
    form.addEventListener('submit', function(event) {
        event.preventDefault();
        
        stripe.createToken(card).then(function(result) {
            if (result.error) {
                // Inform the user if there was an error
                var errorElement = document.getElementById('card-errors');
                errorElement.textContent = result.error.message;
            } else {
                // Send the token to your server
                stripeTokenHandler(result.token);
            }
        });
    });
    
    // Submit the form with the token ID
    function stripeTokenHandler(token) {
        // Insert the token ID into the form so it gets submitted to the server
        var form = document.getElementById('payment-form');
        var hiddenInput = document.createElement('input');
        hiddenInput.setAttribute('type', 'hidden');
        hiddenInput.setAttribute('name', 'stripeToken');
        hiddenInput.setAttribute('value', token.id);
        form.appendChild(hiddenInput);
        
        // Submit the form
        form.submit();
    }
    
    // Tab switching functionality
    function activateTab(tabId) {
        // Hide all tab panes
        document.querySelectorAll('.tab-pane').forEach(function(pane) {
            pane.classList.remove('show', 'active');
        });
        
        // Show the selected tab pane
        document.getElementById(tabId).classList.add('show', 'active');
        
        // Update tab styles
        document.querySelectorAll('.nav-tabs a').forEach(function(tab) {
            tab.style.backgroundColor = '#f8f9fa';
            tab.style.color = '#495057';
        });
        
        document.getElementById(tabId + '-tab').style.backgroundColor = '#fff';
        document.getElementById(tabId + '-tab').style.color = '#007bff';
    }
</script>

<style>
@media (max-width: 768px) {
    .container > div:nth-child(4) {
        grid-template-columns: 1fr !important;
        gap: 1rem !important;
    }
}

.tab-pane {
    display: none;
}

.tab-pane.show.active {
    display: block;
}
</style>

<?php include 'includes/footer.php'; ?>
