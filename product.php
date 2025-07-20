<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$product_id) {
    header('Location: products.php');
    exit();
}

// Get product details
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: products.php');
    exit();
}

// Get compatible car models
$compat_query = "SELECT cm.name as model_name, mk.name as make_name 
                 FROM product_compatibility pc 
                 JOIN car_models cm ON pc.car_model_id = cm.id 
                 JOIN car_makes mk ON cm.make_id = mk.id 
                 WHERE pc.product_id = ? 
                 ORDER BY mk.name, cm.name";
$compat_stmt = $db->prepare($compat_query);
$compat_stmt->execute([$product_id]);
$compatible_models = $compat_stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = $product['name'];
include 'includes/header.php';
?>

<main>
    <div class="container" style="padding: 2rem 20px;">
        <nav style="margin-bottom: 2rem;">
            <a href="products.php" style="color: #007bff; text-decoration: none;">
                <i class="fas fa-arrow-left"></i> Back to Products
            </a>
        </nav>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; margin-bottom: 3rem;">
            <!-- Product Image -->
            <div>
                <div style="background: white; border-radius: 10px; padding: 2rem; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <?php if ($product['image_url']): ?>
                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             style="max-width: 100%; height: auto; border-radius: 10px;">
                    <?php else: ?>
                        <div style="height: 300px; display: flex; align-items: center; justify-content: center; background: #f8f9fa; border-radius: 10px;">
                            <i class="fas fa-cog" style="font-size: 5rem; color: #6c757d;"></i>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Product Info -->
            <div>
                <div style="background: white; border-radius: 10px; padding: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <h1 style="margin-bottom: 1rem; color: #333;"><?php echo htmlspecialchars($product['name']); ?></h1>
                    
                    <div style="margin-bottom: 1rem;">
                        <span style="background: #e9ecef; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.9rem;">
                            Part #: <?php echo htmlspecialchars($product['part_number']); ?>
                        </span>
                    </div>
                    
                    <div style="margin-bottom: 1rem;">
                        <span style="color: #6c757d;">Category: </span>
                        <span><?php echo htmlspecialchars($product['category_name']); ?></span>
                    </div>
                    
                    <div style="margin-bottom: 2rem;">
                        <span style="font-size: 2rem; font-weight: bold; color: #007bff;">
                            <?php echo formatPrice($product['price']); ?>
                        </span>
                    </div>
                    
                    <div style="margin-bottom: 2rem;">
                        <span style="color: <?php echo $product['stock_quantity'] > 0 ? '#28a745' : '#dc3545'; ?>;">
                            <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
                            <?php echo $product['stock_quantity'] > 0 ? 'In Stock (' . $product['stock_quantity'] . ' available)' : 'Out of Stock'; ?>
                        </span>
                    </div>
                    
                    <?php if ($product['stock_quantity'] > 0): ?>
                        <div style="margin-bottom: 2rem;">
                            <button class="btn btn-primary add-to-cart" data-product-id="<?php echo $product['id']; ?>" 
                                    style="font-size: 1.1rem; padding: 1rem 2rem;">
                                <i class="fas fa-cart-plus"></i> Add to Cart
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($product['description']): ?>
                        <div style="margin-bottom: 2rem;">
                            <h3 style="margin-bottom: 1rem;">Description</h3>
                            <p style="line-height: 1.6; color: #555;">
                                <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Compatible Models -->
        <?php if (!empty($compatible_models)): ?>
            <div style="background: white; border-radius: 10px; padding: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h3 style="margin-bottom: 1.5rem;">Compatible Car Models</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <?php foreach ($compatible_models as $model): ?>
                        <div style="background: #f8f9fa; padding: 1rem; border-radius: 5px; text-align: center;">
                            <strong><?php echo htmlspecialchars($model['make_name']); ?></strong><br>
                            <?php echo htmlspecialchars($model['model_name']); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<style>
@media (max-width: 768px) {
    .container > div:first-of-type {
        grid-template-columns: 1fr !important;
        gap: 2rem !important;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
