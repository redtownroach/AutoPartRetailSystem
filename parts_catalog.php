<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

$page_title = 'Complete Parts Catalog';

$database = new Database();
$db = $database->getConnection();

// Get all parts grouped by car make
$query = "SELECT p.*, c.name as category_name, mk.name as make_name, 
                 GROUP_CONCAT(DISTINCT cm.name ORDER BY cm.name SEPARATOR ', ') as compatible_models
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id
          LEFT JOIN product_compatibility pc ON p.id = pc.product_id
          LEFT JOIN car_models cm ON pc.car_model_id = cm.id
          LEFT JOIN car_makes mk ON cm.make_id = mk.id
          GROUP BY p.id
          ORDER BY mk.name, p.name";
$stmt = $db->prepare($query);
$stmt->execute();
$all_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group products by make
$products_by_make = [];
foreach ($all_products as $product) {
    $make = $product['make_name'] ?: 'Universal';
    if (!isset($products_by_make[$make])) {
        $products_by_make[$make] = [];
    }
    $products_by_make[$make][] = $product;
}

include 'includes/header.php';
?>

<main>
    <div class="container" style="padding: 2rem 20px;">
        <h1>Complete Auto Parts Catalog</h1>
        <p style="margin-bottom: 3rem; color: #6c757d; font-size: 1.1rem;">
            Browse our extensive collection of genuine auto parts for 10 different car brands. 
            Each brand has 10 specialized parts covering all major vehicle systems - now featuring over 100 unique parts!
        </p>
         <!-- Search Section -->
    <section class="search-section">
        <div class="container">
            <form class="search-form" action="products.php" method="GET">
                <input type="text" name="search" id="search" class="search-input" 
                       placeholder="Search by part number, name, or description...">
                <select name="make" class="search-select">
                    <option value="">All Car Brands</option>
                    <?php foreach ($car_makes as $make): ?>
                        <option value="<?php echo $make['id']; ?>"><?php echo htmlspecialchars($make['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Search Parts
                </button>
            </form>
        </div>
    </section>
        
        <?php foreach ($products_by_make as $make => $products): ?>
            <div style="margin-bottom: 4rem;">
                <h2 style="color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 0.5rem; margin-bottom: 2rem;">
                    <i class="fas fa-car" style="margin-right: 0.5rem;"></i>
                    <?php echo htmlspecialchars($make); ?> Parts (<?php echo count($products); ?> items)
                </h2>
                
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
                                <p style="color: #6c757d; font-size: 0.9rem; margin-bottom: 0.5rem;">
                                    Category: <?php echo htmlspecialchars($product['category_name']); ?>
                                </p>
                                <?php if ($product['compatible_models']): ?>
                                    <p style="color: #28a745; font-size: 0.85rem; margin-bottom: 1rem;">
                                        <i class="fas fa-check-circle"></i> 
                                        Compatible: <?php echo htmlspecialchars($product['compatible_models']); ?>
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
        
        <!-- Parts Summary -->
        <div style="background: white; border-radius: 10px; padding: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 3rem;">
            <h3 style="margin-bottom: 1.5rem;">Parts Catalog Summary</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <?php foreach ($products_by_make as $make => $products): ?>
                    <div style="text-align: center; padding: 1rem; background: #f8f9fa; border-radius: 5px;">
                        <strong style="color: #007bff;"><?php echo htmlspecialchars($make); ?></strong><br>
                        <span style="color: #6c757d;"><?php echo count($products); ?> Parts Available</span>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div style="margin-top: 2rem; text-align: center;">
                <p style="color: #6c757d;">
                    <strong>Total: <?php echo count($all_products); ?> unique auto parts</strong> covering all major vehicle systems including 
                    engine components, brake systems, suspension parts, electrical components, filters, transmission parts, 
                    cooling systems, exhaust components, body accessories, and interior upgrades.
                </p>
                <div style="margin-top: 1rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
                    <div style="background: #e3f2fd; padding: 1rem; border-radius: 5px;">
                        <strong style="color: #1976d2;">Performance Parts</strong><br>
                        <span style="font-size: 0.9rem;">Turbochargers, Intercoolers, Exhausts</span>
                    </div>
                    <div style="background: #f3e5f5; padding: 1rem; border-radius: 5px;">
                        <strong style="color: #7b1fa2;">Safety Systems</strong><br>
                        <span style="font-size: 0.9rem;">Brakes, ABS, Lighting</span>
                    </div>
                    <div style="background: #e8f5e8; padding: 1rem; border-radius: 5px;">
                        <strong style="color: #388e3c;">Comfort & Style</strong><br>
                        <span style="font-size: 0.9rem;">Interior, Accessories, Body Kits</span>
                    </div>
                    <div style="background: #fff3e0; padding: 1rem; border-radius: 5px;">
                        <strong style="color: #f57c00;">Off-Road Gear</strong><br>
                        <span style="font-size: 0.9rem;">Bull Bars, Snorkels, Winches</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
