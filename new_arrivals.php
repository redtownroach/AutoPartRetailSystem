<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

$page_title = 'New Arrivals';

$database = new Database();
$db = $database->getConnection();

// Get the latest 30 products (our new additions)
$query = "SELECT p.*, c.name as category_name, mk.name as make_name, 
                 GROUP_CONCAT(DISTINCT cm.name ORDER BY cm.name SEPARATOR ', ') as compatible_models
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id
          LEFT JOIN product_compatibility pc ON p.id = pc.product_id
          LEFT JOIN car_models cm ON pc.car_model_id = cm.id
          LEFT JOIN car_makes mk ON cm.make_id = mk.id
          WHERE p.id > 70
          GROUP BY p.id
          ORDER BY p.id DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$new_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group by category for better organization
$products_by_category = [];
foreach ($new_products as $product) {
    $category = $product['category_name'] ?: 'Uncategorized';
    if (!isset($products_by_category[$category])) {
        $products_by_category[$category] = [];
    }
    $products_by_category[$category][] = $product;
}

include 'includes/header.php';
?>

<main>
    <div class="container" style="padding: 2rem 20px;">
        <!-- Hero Section -->
        <div style="background: linear-gradient(135deg, #007bff, #0056b3); color: white; padding: 3rem 2rem; border-radius: 15px; text-align: center; margin-bottom: 3rem;">
            <h1 style="font-size: 2.5rem; margin-bottom: 1rem;">🆕 New Arrivals</h1>
            <p style="font-size: 1.2rem; margin-bottom: 2rem;">
                Discover our latest collection of 30 premium auto parts across all major brands
            </p>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem; margin-top: 2rem;">
                <div style="background: rgba(255,255,255,0.1); padding: 1.5rem; border-radius: 10px;">
                    <h3 style="font-size: 2rem; margin-bottom: 0.5rem;">30</h3>
                    <p>New Parts Added</p>
                </div>
                <div style="background: rgba(255,255,255,0.1); padding: 1.5rem; border-radius: 10px;">
                    <h3 style="font-size: 2rem; margin-bottom: 0.5rem;">10</h3>
                    <p>Car Brands Covered</p>
                </div>
                <div style="background: rgba(255,255,255,0.1); padding: 1.5rem; border-radius: 10px;">
                    <h3 style="font-size: 2rem; margin-bottom: 0.5rem;">100+</h3>
                    <p>Total Parts Available</p>
                </div>
            </div>
        </div>

        <!-- Featured New Products -->
        <div style="margin-bottom: 3rem;">
            <h2 style="color: #007bff; margin-bottom: 2rem;">🌟 Featured New Arrivals</h2>
            <div class="products-grid">
                <?php 
                $featured_new = array_filter($new_products, function($product) {
                    return $product['is_featured'] == 1;
                });
                foreach ($featured_new as $product): 
                ?>
                    <div class="product-card" style="border: 2px solid #007bff;">
                        <div style="background: #007bff; color: white; padding: 0.5rem; text-align: center; font-weight: bold; font-size: 0.9rem;">
                            NEW ARRIVAL
                        </div>
                        <div class="product-image">
                            <?php if ($product['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <?php else: ?>
                                <i class="fas fa-cog"></i>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="product-part-number">Part #: <?php echo htmlspecialchars($product['part_number']); ?></p>
                            <p style="color: #007bff; font-weight: bold; margin-bottom: 0.5rem;">
                                <?php echo htmlspecialchars($product['make_name']); ?>
                            </p>
                            <p style="color: #6c757d; font-size: 0.9rem; margin-bottom: 1rem;">
                                <?php echo htmlspecialchars($product['category_name']); ?>
                            </p>
                            <p class="product-price"><?php echo formatPrice($product['price']); ?></p>
                            <div class="product-actions">
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-secondary">View Details</a>
                                <button class="btn btn-primary add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                                    <i class="fas fa-cart-plus"></i> Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- All New Products by Category -->
        <h2 style="color: #007bff; margin-bottom: 2rem;">📦 All New Products by Category</h2>
        
        <?php foreach ($products_by_category as $category => $products): ?>
            <div style="margin-bottom: 3rem;">
                <h3 style="color: #333; border-left: 4px solid #007bff; padding-left: 1rem; margin-bottom: 1.5rem;">
                    <?php echo htmlspecialchars($category); ?> (<?php echo count($products); ?> new items)
                </h3>
                
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <?php if ($product['image_url']): ?>
                                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php else: ?>
                                    <i class="fas fa-cog"></i>
                                <?php endif; ?>
                            </div>
                            <div class="product-info">
                                <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p class="product-part-number">Part #: <?php echo htmlspecialchars($product['part_number']); ?></p>
                                <p style="color: #007bff; font-weight: bold; margin-bottom: 0.5rem;">
                                    <?php echo htmlspecialchars($product['make_name']); ?>
                                </p>
                                <?php if ($product['compatible_models']): ?>
                                    <p style="color: #28a745; font-size: 0.85rem; margin-bottom: 1rem;">
                                        <i class="fas fa-check-circle"></i> 
                                        <?php echo htmlspecialchars($product['compatible_models']); ?>
                                    </p>
                                <?php endif; ?>
                                <p class="product-price"><?php echo formatPrice($product['price']); ?></p>
                                <div style="margin-bottom: 1rem;">
                                    <span style="color: <?php echo $product['stock_quantity'] > 0 ? '#28a745' : '#dc3545'; ?>; font-size: 0.9rem;">
                                        <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
                                        <?php echo $product['stock_quantity'] > 0 ? 'In Stock (' . $product['stock_quantity'] . ')' : 'Out of Stock'; ?>
                                    </span>
                                </div>
                                <div class="product-actions">
                                    <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-secondary">View Details</a>
                                    <?php if ($product['stock_quantity'] > 0): ?>
                                        <button class="btn btn-primary add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                                            <i class="fas fa-cart-plus"></i> Add to Cart
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Call to Action -->
        <div style="background: #f8f9fa; padding: 3rem 2rem; border-radius: 15px; text-align: center; margin-top: 3rem;">
            <h3 style="margin-bottom: 1rem;">Looking for Something Specific?</h3>
            <p style="color: #6c757d; margin-bottom: 2rem;">
                Can't find the part you need? Our extensive catalog has over 100 parts for 10 different car brands.
            </p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="products.php" class="btn btn-primary">Browse All Products</a>
                <a href="parts_catalog.php" class="btn btn-secondary">View Full Catalog</a>
                <a href="categories.php" class="btn btn-secondary">Shop by Category</a>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
