<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

$page_title = 'Home';

$database = new Database();
$db = $database->getConnection();

// Get featured products
$query = "SELECT p.*, c.name as category_name FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.is_featured = 1 
          ORDER BY p.created_at DESC LIMIT 8";
$stmt = $db->prepare($query);
$stmt->execute();
$featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get car makes
$query = "SELECT * FROM car_makes ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$car_makes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for quick access
$query = "SELECT c.*, COUNT(p.id) as product_count 
          FROM categories c 
          LEFT JOIN products p ON c.id = p.category_id 
          GROUP BY c.id 
          ORDER BY product_count DESC 
          LIMIT 6";
$stmt = $db->prepare($query);
$stmt->execute();
$popular_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Define category icons
$category_icons = [
    'Engine Parts' => 'fas fa-engine',
    'Brake System' => 'fas fa-stop-circle',
    'Suspension' => 'fas fa-car-crash',
    'Electrical' => 'fas fa-bolt',
    'Filters' => 'fas fa-filter',
    'Transmission' => 'fas fa-cogs',
    'Cooling System' => 'fas fa-snowflake',
    'Exhaust System' => 'fas fa-wind',
    'Body Parts' => 'fas fa-car',
    'Interior' => 'fas fa-chair',
    'Performance Upgrades' => 'fas fa-tachometer-alt',
    'Maintenance & Service' => 'fas fa-tools',
    'Tires & Wheels' => 'fas fa-tire',
    'Audio & Electronics' => 'fas fa-music',
    'Security & Safety' => 'fas fa-shield-alt',
    'Exterior Accessories' => 'fas fa-car-side',
    'Interior Accessories' => 'fas fa-couch',
    'Tools & Equipment' => 'fas fa-wrench',
    'Fluids & Chemicals' => 'fas fa-flask',
    'Gaskets & Seals' => 'fas fa-ring'
];

// Car brand icons
$brand_icons = [
    'Toyota' => 'fas fa-car',
    'Nissan' => 'fas fa-truck',
    'Honda' => 'fas fa-car-side',
    'Mazda' => 'fas fa-car-alt',
    'Mitsubishi' => 'fas fa-truck-pickup',
    'Subaru' => 'fas fa-car',
    'Isuzu' => 'fas fa-truck',
    'Suzuki' => 'fas fa-car-side',
    'Hyundai' => 'fas fa-car',
    'Volkswagen' => 'fas fa-car-alt'
];

include 'includes/header.php';
?>

<main>
    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1><i class="fas fa-car-side"></i> Premium Auto Parts in Tanzania</h1>
            <p>Your trusted source for genuine car parts from all major brands. Quality guaranteed, prices unmatched.</p>
        </div>
        
    </section>

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

    <!-- Quick Categories -->
    <section style="padding: 3rem 0; background: var(--white);">
        <div class="container">
            <h2 class="section-title">Shop by Category</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <?php foreach ($popular_categories as $category): ?>
                    <a href="products.php?category=<?php echo $category['id']; ?>" style="text-decoration: none;">
                        <div style="background: var(--gray-50); border: 2px solid var(--gray-200); border-radius: var(--border-radius-lg); padding: 1.5rem; transition: all 0.3s ease; text-align: center; height: 100%;" 
                             onmouseover="this.style.borderColor='var(--primary-color)'; this.style.transform='translateY(-4px)'; this.style.background='var(--white)'"
                             onmouseout="this.style.borderColor='var(--gray-200)'; this.style.transform='translateY(0)'; this.style.background='var(--gray-50)'">
                            <div style="width: 60px; height: 60px; background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; color: var(--white); font-size: 1.5rem;">
                                <i class="<?php echo isset($category_icons[$category['name']]) ? $category_icons[$category['name']] : 'fas fa-cog'; ?>"></i>
                            </div>
                            <h4 style="color: var(--gray-900); margin-bottom: 0.5rem; font-weight: 700; font-size: 1rem;"><?php echo htmlspecialchars($category['name']); ?></h4>
                            <p style="color: var(--primary-color); font-weight: 600; font-size: 0.875rem;"><?php echo $category['product_count']; ?> Products</p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            <div style="text-align: center;">
                <a href="categories.php" class="btn btn-outline">
                    <i class="fas fa-th-large"></i> View All Categories
                </a>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <section class="featured-section">
        <div class="container">
            <h2 class="section-title">Featured Products</h2>
            <div class="products-grid">
                <?php foreach ($featured_products as $product): ?>
                    <div class="product-card featured">
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
                            <span class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></span>
                            <p class="product-price"><?php echo formatPrice($product['price']); ?></p>
                            <div class="product-stock <?php echo $product['stock_quantity'] > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                                <i class="fas fa-circle" style="font-size: 0.5rem; margin-right: 0.5rem;"></i>
                                <?php echo $product['stock_quantity'] > 0 ? 'In Stock (' . $product['stock_quantity'] . ')' : 'Out of Stock'; ?>
                            </div>
                            <div class="product-actions">
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-secondary">
                                    <i class="fas fa-eye"></i> Details
                                </a>
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
            
            <div style="text-align: center; margin-top: 2rem;">
                <a href="products.php" class="btn btn-primary">
                    <i class="fas fa-cogs"></i> View All Products
                </a>
            </div>
        </div>
    </section>

    <!-- Car Brands 
    <section class="brands-section">
        <div class="container">
            <h2 class="section-title">Shop by Car Brand</h2>
            <div class="brands-grid">
                <?php foreach ($car_makes as $make): ?>
                    <a href="products.php?make=<?php echo $make['id']; ?>" class="brand-item">
                        <div class="brand-logo">
                            <i class="<?php echo isset($brand_icons[$make['name']]) ? $brand_icons[$make['name']] : 'fas fa-car'; ?>"></i>
                        </div>
                        <h4 class="brand-name"><?php echo htmlspecialchars($make['name']); ?></h4>
                        <p class="brand-count">Premium Parts Available</p>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section> -->

    <!-- Features Section -->
    <section style="padding: 4rem 0; background: var(--gray-50);">
        <div class="container">
            <h2 class="section-title">Why Choose AutoParts TZ?</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem;">
                <div style="background: var(--white); padding: 2rem; border-radius: var(--border-radius-xl); box-shadow: var(--shadow); text-align: center; border: 2px solid var(--gray-200);">
                    <div style="width: 80px; height: 80px; background: linear-gradient(135deg, var(--success-color), #059669); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; color: var(--white); font-size: 2rem;">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <h3 style="margin-bottom: 1rem; color: var(--gray-900); font-weight: 700;">Genuine Parts</h3>
                    <p style="color: var(--gray-600); line-height: 1.6;">All our parts are genuine OEM or high-quality aftermarket parts with warranty coverage.</p>
                </div>
                
                <div style="background: var(--white); padding: 2rem; border-radius: var(--border-radius-xl); box-shadow: var(--shadow); text-align: center; border: 2px solid var(--gray-200);">
                    <div style="width: 80px; height: 80px; background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; color: var(--white); font-size: 2rem;">
                        <i class="fas fa-shipping-fast"></i>
                    </div>
                    <h3 style="margin-bottom: 1rem; color: var(--gray-900); font-weight: 700;">Fast Delivery</h3>
                    <p style="color: var(--gray-600); line-height: 1.6;">Quick delivery across Tanzania with tracking. Same-day delivery available in Dar es Salaam.</p>
                </div>
                
                <div style="background: var(--white); padding: 2rem; border-radius: var(--border-radius-xl); box-shadow: var(--shadow); text-align: center; border: 2px solid var(--gray-200);">
                    <div style="width: 80px; height: 80px; background: linear-gradient(135deg, var(--secondary-color), #d97706); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; color: var(--white); font-size: 2rem;">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3 style="margin-bottom: 1rem; color: var(--gray-900); font-weight: 700;">Expert Support</h3>
                    <p style="color: var(--gray-600); line-height: 1.6;">Our automotive experts are here to help you find the right parts for your vehicle.</p>
                </div>
                
                <div style="background: var(--white); padding: 2rem; border-radius: var(--border-radius-xl); box-shadow: var(--shadow); text-align: center; border: 2px solid var(--gray-200);">
                    <div style="width: 80px; height: 80px; background: linear-gradient(135deg, var(--info-color), #2563eb); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; color: var(--white); font-size: 2rem;">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <h3 style="margin-bottom: 1rem; color: var(--gray-900); font-weight: 700;">Secure Payment</h3>
                    <p style="color: var(--gray-600); line-height: 1.6;">Multiple payment options including M-Pesa, credit cards, and bank transfers.</p>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
