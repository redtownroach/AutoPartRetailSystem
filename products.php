<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

$page_title = 'Products';

$database = new Database();
$db = $database->getConnection();

// Get filter parameters
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$make_id = isset($_GET['make']) ? (int)$_GET['make'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Build query
$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(p.name LIKE ? OR p.part_number LIKE ? OR p.description LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($category_id) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_id;
}

if ($make_id) {
    $where_conditions[] = "EXISTS (
        SELECT 1 
        FROM product_compatibility pc 
        JOIN car_models cm ON pc.car_model_id = cm.id 
        WHERE pc.product_id = p.id 
        AND cm.make_id = ?
    )";
    $params[] = $make_id;
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_query = "SELECT COUNT(DISTINCT p.id) 
                FROM products p 
                LEFT JOIN product_compatibility pc ON p.id = pc.product_id 
                LEFT JOIN car_models cm ON pc.car_model_id = cm.id 
                $where_clause";
$count_stmt = $db->prepare($count_query);
$count_stmt->execute($params);
$total_products = $count_stmt->fetchColumn();
$total_pages = ceil($total_products / $per_page);

// Get products with compatibility information
$query = "SELECT DISTINCT p.*, c.name as category_name,
          GROUP_CONCAT(DISTINCT cm.make_id) as compatible_make_ids
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          LEFT JOIN product_compatibility pc ON p.id = pc.product_id
          LEFT JOIN car_models cm ON pc.car_model_id = cm.id
          $where_clause 
          GROUP BY p.id
          ORDER BY p.created_at DESC 
          LIMIT $per_page OFFSET $offset";
$stmt = $db->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filter
$cat_query = "SELECT * FROM categories ORDER BY name";
$cat_stmt = $db->prepare($cat_query);
$cat_stmt->execute();
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get car makes for filter
$make_query = "SELECT * FROM car_makes ORDER BY name";
$make_stmt = $db->prepare($make_query);
$make_stmt->execute();
$car_makes = $make_stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<main>
    <div class="container" style="padding: 2rem 20px;">
        <h1>Products</h1>
        
        <!-- Filters -->
        <div style="background: white; padding: 1.5rem; border-radius: 10px; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <form method="GET" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: end;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Search</label>
                    <input type="text" name="search" class="form-control" style="min-width: 200px;" 
                           value="<?php echo htmlspecialchars($search); ?>" placeholder="Part number or name">
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Category</label>
                    <select name="category" class="form-control">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" 
                                    <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                 <!-- Car MAKE REMOVED
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Car Make</label>
                    <select name="make" class="form-control">
                        <option value="">All Makes</option>
                        <?php foreach ($car_makes as $make): ?>
                            <option value="<?php echo $make['id']; ?>" 
                                    <?php echo $make_id == $make['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($make['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                -->
                
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="products.php" class="btn btn-secondary">Clear</a>
            </form>
        </div>
        
        <!-- Results -->
        <div style="margin-bottom: 1rem;">
            <p>Showing <?php echo count($products); ?> of <?php echo $total_products; ?> products</p>
        </div>
        
        <?php if (empty($products)): ?>
            <div class="alert alert-info">
                <p>No products found matching your criteria.</p>
            </div>
        <?php else: ?>
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
                            <p style="color: #6c757d; font-size: 0.9rem;">
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
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div style="text-align: center; margin-top: 2rem;">
                    <?php
                    $query_params = $_GET;
                    for ($i = 1; $i <= $total_pages; $i++):
                        $query_params['page'] = $i;
                        $url = 'products.php?' . http_build_query($query_params);
                        $active = $i == $page ? 'style="background: #007bff; color: white;"' : '';
                    ?>
                        <a href="<?php echo $url; ?>" class="btn btn-secondary" <?php echo $active; ?>>
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>