<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

$page_title = 'Shopping Cart';

$database = new Database();
$db = $database->getConnection();

$cart_items = [];
$cart_total = 0;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
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
}

include 'includes/header.php';
?>

<main>
    <div class="container" style="padding: 2rem 20px;">
        <h1>Shopping Cart</h1>
        
        <?php if (empty($cart_items)): ?>
            <div class="alert alert-info">
                <p>Your cart is empty. <a href="products.php">Continue shopping</a></p>
            </div>
        <?php else: ?>
            <div class="cart-table">
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Part Number</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items as $item): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 1rem;">
                                        <div style="width: 60px; height: 60px; background: #f8f9fa; border-radius: 5px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-cog"></i>
                                        </div>
                                        <div>
                                            <strong><?php echo htmlspecialchars($item['product']['name']); ?></strong>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($item['product']['part_number']); ?></td>
                                <td><?php echo formatPrice($item['product']['price']); ?></td>
                                <td>
                                    <input type="number" class="quantity-input" 
                                           value="<?php echo $item['quantity']; ?>" 
                                           min="1" max="<?php echo $item['product']['stock_quantity']; ?>"
                                           data-product-id="<?php echo $item['product']['id']; ?>">
                                </td>
                                <td><?php echo formatPrice($item['subtotal']); ?></td>
                                <td>
                                    <button class="btn btn-danger btn-sm remove-from-cart" 
                                            data-product-id="<?php echo $item['product']['id']; ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4"><strong>Total:</strong></td>
                            <td><strong><?php echo formatPrice($cart_total); ?></strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <div style="text-align: right; margin-top: 2rem;">
                <a href="products.php" class="btn btn-secondary">Continue Shopping</a>
                <a href="checkout.php" class="btn btn-primary">Proceed to Checkout</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
