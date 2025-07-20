<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

$page_title = 'Categories';

$database = new Database();
$db = $database->getConnection();

// Get categories with product count
$query = "SELECT c.*, COUNT(p.id) as product_count 
          FROM categories c 
          LEFT JOIN products p ON c.id = p.category_id 
          GROUP BY c.id 
          ORDER BY c.name";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

include 'includes/header.php';
?>

<main>
    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1><i class="fas fa-th-large"></i> Product Categories</h1>
            <p>Explore our comprehensive range of automotive parts organized by category</p>
        </div>
    </section>

    <div class="container" style="padding: 4rem 1.5rem;">
        <div class="categories-grid">
            <?php foreach ($categories as $category): ?>
                <div class="category-card">
                    <div class="category-icon">
                        <i class="<?php echo isset($category_icons[$category['name']]) ? $category_icons[$category['name']] : 'fas fa-cog'; ?>"></i>
                    </div>
                    <h3 class="category-title"><?php echo htmlspecialchars($category['name']); ?></h3>
                    <p class="category-description"><?php echo htmlspecialchars($category['description']); ?></p>
                    <div style="margin-bottom: 1.5rem;">
                        <span style="background: var(--primary-color); color: var(--white); padding: 0.5rem 1rem; border-radius: var(--border-radius); font-weight: 600; font-size: 0.875rem;">
                            <?php echo $category['product_count']; ?> Products Available
                        </span>
                    </div>
                    <a href="products.php?category=<?php echo $category['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-arrow-right"></i> Browse Products
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Category Stats -->
        <div style="margin-top: 4rem; background: var(--white); border-radius: var(--border-radius-xl); padding: 3rem; box-shadow: var(--shadow-lg); text-align: center;">
            <h3 style="margin-bottom: 2rem; color: var(--gray-900); font-size: 2rem;">Category Overview</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem;">
                <div style="padding: 1.5rem; background: var(--gray-50); border-radius: var(--border-radius-lg);">
                    <div style="font-size: 2.5rem; font-weight: 800; color: var(--primary-color); margin-bottom: 0.5rem;">
                        <?php echo count($categories); ?>
                    </div>
                    <div style="color: var(--gray-600); font-weight: 600;">Total Categories</div>
                </div>
                <div style="padding: 1.5rem; background: var(--gray-50); border-radius: var(--border-radius-lg);">
                    <div style="font-size: 2.5rem; font-weight: 800; color: var(--success-color); margin-bottom: 0.5rem;">
                        <?php 
                        $total_products = array_sum(array_column($categories, 'product_count'));
                        echo $total_products;
                        ?>
                    </div>
                    <div style="color: var(--gray-600); font-weight: 600;">Total Products</div>
                </div>
                <div style="padding: 1.5rem; background: var(--gray-50); border-radius: var(--border-radius-lg);">
                    <div style="font-size: 2.5rem; font-weight: 800; color: var(--secondary-color); margin-bottom: 0.5rem;">
                        10
                    </div>
                    <div style="color: var(--gray-600); font-weight: 600;">Car Brands</div>
                </div>
                <div style="padding: 1.5rem; background: var(--gray-50); border-radius: var(--border-radius-lg);">
                    <div style="font-size: 2.5rem; font-weight: 800; color: var(--info-color); margin-bottom: 0.5rem;">
                        24/7
                    </div>
                    <div style="color: var(--gray-600); font-weight: 600;">Support</div>
                </div>
            </div>
        </div>

        <!-- Popular Categories -->
        <div style="margin-top: 4rem;">
            <h3 style="text-align: center; margin-bottom: 2rem; color: var(--gray-900); font-size: 2rem;">Most Popular Categories</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                <?php 
                // Sort categories by product count and get top 6
                usort($categories, function($a, $b) {
                    return $b['product_count'] - $a['product_count'];
                });
                $popular_categories = array_slice($categories, 0, 6);
                
                foreach ($popular_categories as $category): 
                ?>
                    <a href="products.php?category=<?php echo $category['id']; ?>" style="text-decoration: none;">
                        <div style="background: var(--white); border: 2px solid var(--gray-200); border-radius: var(--border-radius-lg); padding: 1.5rem; transition: all 0.3s ease; text-align: center;" 
                             onmouseover="this.style.borderColor='var(--primary-color)'; this.style.transform='translateY(-4px)'"
                             onmouseout="this.style.borderColor='var(--gray-200)'; this.style.transform='translateY(0)'">
                            <div style="width: 60px; height: 60px; background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; color: var(--white); font-size: 1.5rem;">
                                <i class="<?php echo isset($category_icons[$category['name']]) ? $category_icons[$category['name']] : 'fas fa-cog'; ?>"></i>
                            </div>
                            <h4 style="color: var(--gray-900); margin-bottom: 0.5rem; font-weight: 700;"><?php echo htmlspecialchars($category['name']); ?></h4>
                            <p style="color: var(--primary-color); font-weight: 600; font-size: 0.875rem;"><?php echo $category['product_count']; ?> Products</p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
