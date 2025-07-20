<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

$page_title = 'Inventory Dashboard';

$database = new Database();
$db = $database->getConnection();

// Get overall inventory stats
$stats_query = "
    SELECT 
        COUNT(*) as total_parts,
        SUM(stock_quantity) as total_stock,
        AVG(stock_quantity) as avg_stock_per_part,
        COUNT(CASE WHEN stock_quantity > 0 THEN 1 END) as parts_in_stock,
        COUNT(CASE WHEN stock_quantity >= 10 THEN 1 END) as parts_well_stocked,
        COUNT(CASE WHEN is_featured = 1 THEN 1 END) as featured_parts,
        SUM(price * stock_quantity) as total_inventory_value
    FROM products
";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Get parts by brand
$brand_query = "
    SELECT 
        CASE 
            WHEN SUBSTRING(part_number, 1, 2) = 'TY' THEN 'Toyota'
            WHEN SUBSTRING(part_number, 1, 2) = 'NS' THEN 'Nissan'
            WHEN SUBSTRING(part_number, 1, 2) = 'HD' THEN 'Honda'
            WHEN SUBSTRING(part_number, 1, 2) = 'MZ' THEN 'Mazda'
            WHEN SUBSTRING(part_number, 1, 2) = 'MT' THEN 'Mitsubishi'
            WHEN SUBSTRING(part_number, 1, 2) = 'SB' THEN 'Subaru'
            WHEN SUBSTRING(part_number, 1, 2) = 'IZ' THEN 'Isuzu'
            WHEN SUBSTRING(part_number, 1, 2) = 'SZ' THEN 'Suzuki'
            WHEN SUBSTRING(part_number, 1, 2) = 'HY' THEN 'Hyundai'
            WHEN SUBSTRING(part_number, 1, 2) = 'VW' THEN 'Volkswagen'
            WHEN SUBSTRING(part_number, 1, 2) = 'UN' THEN 'Universal'
            ELSE 'Other'
        END as brand_name,
        COUNT(*) as parts_count,
        SUM(stock_quantity) as total_stock,
        COUNT(CASE WHEN stock_quantity > 0 THEN 1 END) as available_parts,
        ROUND(AVG(price), 0) as avg_price
    FROM products 
    GROUP BY SUBSTRING(part_number, 1, 2)
    ORDER BY parts_count DESC
";
$brand_stmt = $db->prepare($brand_query);
$brand_stmt->execute();
$brand_stats = $brand_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get low stock alerts (less than 5 items)
$low_stock_query = "
    SELECT part_number, name, stock_quantity, price
    FROM products 
    WHERE stock_quantity < 5 AND stock_quantity > 0
    ORDER BY stock_quantity ASC
    LIMIT 10
";
$low_stock_stmt = $db->prepare($low_stock_query);
$low_stock_stmt->execute();
$low_stock_items = $low_stock_stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<main>
    <div class="container" style="padding: 2rem 1.5rem;">
        <h1><i class="fas fa-warehouse"></i> Inventory Dashboard</h1>
        
        <!-- Overall Statistics -->
        <div style="background: var(--white); border-radius: var(--border-radius-xl); padding: 2rem; margin-bottom: 2rem; box-shadow: var(--shadow);">
            <h2 style="margin-bottom: 1.5rem; color: var(--gray-900);">Inventory Overview</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                <div style="text-align: center; padding: 1.5rem; background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); color: var(--white); border-radius: var(--border-radius);">
                    <div style="font-size: 2.5rem; font-weight: 800; margin-bottom: 0.5rem;">
                        <?php echo number_format($stats['total_parts']); ?>
                    </div>
                    <div style="font-weight: 600; opacity: 0.9;">Total Parts</div>
                </div>
                <div style="text-align: center; padding: 1.5rem; background: linear-gradient(135deg, var(--success-color), #059669); color: var(--white); border-radius: var(--border-radius);">
                    <div style="font-size: 2.5rem; font-weight: 800; margin-bottom: 0.5rem;">
                        <?php echo number_format($stats['parts_in_stock']); ?>
                    </div>
                    <div style="font-weight: 600; opacity: 0.9;">Parts Available</div>
                </div>
                <div style="text-align: center; padding: 1.5rem; background: linear-gradient(135deg, var(--secondary-color), #d97706); color: var(--white); border-radius: var(--border-radius);">
                    <div style="font-size: 2.5rem; font-weight: 800; margin-bottom: 0.5rem;">
                        <?php echo number_format($stats['total_stock']); ?>
                    </div>
                    <div style="font-weight: 600; opacity: 0.9;">Total Stock Units</div>
                </div>
                <div style="text-align: center; padding: 1.5rem; background: linear-gradient(135deg, var(--info-color), #2563eb); color: var(--white); border-radius: var(--border-radius);">
                    <div style="font-size: 1.8rem; font-weight: 800; margin-bottom: 0.5rem;">
                        <?php echo formatPrice($stats['total_inventory_value']); ?>
                    </div>
                    <div style="font-weight: 600; opacity: 0.9;">Inventory Value</div>
                </div>
            </div>
        </div>

        <!-- Brand Distribution -->
        <div style="background: var(--white); border-radius: var(--border-radius-xl); padding: 2rem; margin-bottom: 2rem; box-shadow: var(--shadow);">
            <h2 style="margin-bottom: 1.5rem; color: var(--gray-900);">Parts by Brand</h2>
            <div class="data-table">
                <table>
                    <thead>
                        <tr>
                            <th>Brand</th>
                            <th>Total Parts</th>
                            <th>Available Parts</th>
                            <th>Total Stock</th>
                            <th>Avg Price</th>
                            <th>Availability %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($brand_stats as $brand): ?>
                            <tr>
                                <td>
                                    <strong style="color: var(--primary-color);">
                                        <i class="fas fa-car"></i> <?php echo htmlspecialchars($brand['brand_name']); ?>
                                    </strong>
                                </td>
                                <td><?php echo number_format($brand['parts_count']); ?></td>
                                <td>
                                    <span style="color: var(--success-color); font-weight: 600;">
                                        <?php echo number_format($brand['available_parts']); ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($brand['total_stock']); ?></td>
                                <td><?php echo formatPrice($brand['avg_price']); ?></td>
                                <td>
                                    <?php 
                                    $availability = ($brand['available_parts'] / $brand['parts_count']) * 100;
                                    $color = $availability >= 90 ? 'var(--success-color)' : 
                                            ($availability >= 70 ? 'var(--warning-color)' : 'var(--danger-color)');
                                    ?>
                                    <span style="color: <?php echo $color; ?>; font-weight: 600;">
                                        <?php echo number_format($availability, 1); ?>%
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Low Stock Alerts -->
        <?php if (!empty($low_stock_items)): ?>
            <div style="background: var(--white); border: 2px solid var(--warning-color); border-radius: var(--border-radius-xl); padding: 2rem; margin-bottom: 2rem; box-shadow: var(--shadow);">
                <h2 style="margin-bottom: 1.5rem; color: var(--warning-color); display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-exclamation-triangle"></i> Low Stock Alerts
                </h2>
                <div class="data-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Part Number</th>
                                <th>Product Name</th>
                                <th>Stock Remaining</th>
                                <th>Price</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($low_stock_items as $item): ?>
                                <tr>
                                    <td>
                                        <code style="background: var(--gray-100); padding: 0.25rem 0.5rem; border-radius: 3px; font-weight: 600;">
                                            <?php echo htmlspecialchars($item['part_number']); ?>
                                        </code>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td>
                                        <span style="color: var(--danger-color); font-weight: 700; font-size: 1.1rem;">
                                            <?php echo $item['stock_quantity']; ?> left
                                        </span>
                                    </td>
                                    <td><?php echo formatPrice($item['price']); ?></td>
                                    <td>
                                        <span style="background: var(--danger-color); color: var(--white); padding: 0.25rem 0.75rem; border-radius: var(--border-radius); font-weight: 600; font-size: 0.875rem;">
                                            REORDER SOON
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div style="background: var(--gray-50); border-radius: var(--border-radius-xl); padding: 2rem; text-align: center;">
            <h3 style="margin-bottom: 1.5rem; color: var(--gray-900);">Quick Actions</h3>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="products.php" class="btn btn-primary">
                    <i class="fas fa-cogs"></i> View All Products
                </a>
                <a href="categories.php" class="btn btn-secondary">
                    <i class="fas fa-th-large"></i> Browse Categories
                </a>
                <a href="compatibility_report.php" class="btn btn-secondary">
                    <i class="fas fa-chart-bar"></i> Compatibility Report
                </a>
                <a href="new_arrivals.php" class="btn btn-success">
                    <i class="fas fa-star"></i> New Arrivals
                </a>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
