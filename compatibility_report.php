<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

$page_title = 'Compatibility Report';

$database = new Database();
$db = $database->getConnection();

// Get compatibility statistics
$stats_query = "
    SELECT 
        COUNT(DISTINCT pc.product_id) as products_with_compatibility,
        COUNT(pc.id) as total_compatibility_records,
        COUNT(DISTINCT pc.car_model_id) as models_covered
    FROM product_compatibility pc
";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Get compatibility by brand
$brand_query = "
    SELECT 
        mk.name as brand,
        COUNT(DISTINCT pc.product_id) as products_count,
        COUNT(pc.id) as compatibility_records
    FROM car_makes mk
    JOIN car_models cm ON mk.id = cm.make_id
    JOIN product_compatibility pc ON cm.id = pc.car_model_id
    GROUP BY mk.id, mk.name
    ORDER BY products_count DESC
";
$brand_stmt = $db->prepare($brand_query);
$brand_stmt->execute();
$brand_stats = $brand_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get universal parts (compatible with 4+ models)
$universal_query = "
    SELECT 
        p.part_number,
        p.name,
        COUNT(pc.car_model_id) as compatible_models,
        GROUP_CONCAT(DISTINCT CONCAT(mk.name, ' ', cm.name) ORDER BY mk.name SEPARATOR ', ') as models
    FROM products p
    JOIN product_compatibility pc ON p.id = pc.product_id
    JOIN car_models cm ON pc.car_model_id = cm.id
    JOIN car_makes mk ON cm.make_id = mk.id
    GROUP BY p.id, p.part_number, p.name
    HAVING COUNT(pc.car_model_id) >= 4
    ORDER BY compatible_models DESC
    LIMIT 20
";
$universal_stmt = $db->prepare($universal_query);
$universal_stmt->execute();
$universal_parts = $universal_stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<main>
    <div class="container" style="padding: 2rem 1.5rem;">
        <h1><i class="fas fa-chart-bar"></i> Product Compatibility Report</h1>
        
        <!-- Overall Statistics -->
        <div style="background: var(--white); border-radius: var(--border-radius-xl); padding: 2rem; margin-bottom: 2rem; box-shadow: var(--shadow);">
            <h2 style="margin-bottom: 1.5rem; color: var(--gray-900);">Overall Statistics</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                <div style="text-align: center; padding: 1.5rem; background: var(--gray-50); border-radius: var(--border-radius);">
                    <div style="font-size: 2.5rem; font-weight: 800; color: var(--primary-color); margin-bottom: 0.5rem;">
                        <?php echo number_format($stats['products_with_compatibility']); ?>
                    </div>
                    <div style="color: var(--gray-600); font-weight: 600;">Products with Compatibility</div>
                </div>
                <div style="text-align: center; padding: 1.5rem; background: var(--gray-50); border-radius: var(--border-radius);">
                    <div style="font-size: 2.5rem; font-weight: 800; color: var(--success-color); margin-bottom: 0.5rem;">
                        <?php echo number_format($stats['total_compatibility_records']); ?>
                    </div>
                    <div style="color: var(--gray-600); font-weight: 600;">Total Compatibility Records</div>
                </div>
                <div style="text-align: center; padding: 1.5rem; background: var(--gray-50); border-radius: var(--border-radius);">
                    <div style="font-size: 2.5rem; font-weight: 800; color: var(--secondary-color); margin-bottom: 0.5rem;">
                        <?php echo number_format($stats['models_covered']); ?>
                    </div>
                    <div style="color: var(--gray-600); font-weight: 600;">Car Models Covered</div>
                </div>
            </div>
        </div>

        <!-- Compatibility by Brand -->
        <div style="background: var(--white); border-radius: var(--border-radius-xl); padding: 2rem; margin-bottom: 2rem; box-shadow: var(--shadow);">
            <h2 style="margin-bottom: 1.5rem; color: var(--gray-900);">Compatibility by Car Brand</h2>
            <div class="data-table">
                <table>
                    <thead>
                        <tr>
                            <th>Car Brand</th>
                            <th>Products Available</th>
                            <th>Compatibility Records</th>
                            <th>Coverage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($brand_stats as $brand): ?>
                            <tr>
                                <td>
                                    <strong style="color: var(--primary-color);">
                                        <i class="fas fa-car"></i> <?php echo htmlspecialchars($brand['brand']); ?>
                                    </strong>
                                </td>
                                <td><?php echo number_format($brand['products_count']); ?></td>
                                <td><?php echo number_format($brand['compatibility_records']); ?></td>
                                <td>
                                    <?php 
                                    $coverage_percentage = ($brand['products_count'] / $stats['products_with_compatibility']) * 100;
                                    $color = $coverage_percentage > 15 ? 'var(--success-color)' : 
                                            ($coverage_percentage > 10 ? 'var(--warning-color)' : 'var(--danger-color)');
                                    ?>
                                    <span style="color: <?php echo $color; ?>; font-weight: 600;">
                                        <?php echo number_format($coverage_percentage, 1); ?>%
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Universal Parts -->
        <div style="background: var(--white); border-radius: var(--border-radius-xl); padding: 2rem; box-shadow: var(--shadow);">
            <h2 style="margin-bottom: 1.5rem; color: var(--gray-900);">Universal Parts (4+ Compatible Models)</h2>
            <div class="data-table">
                <table>
                    <thead>
                        <tr>
                            <th>Part Number</th>
                            <th>Product Name</th>
                            <th>Compatible Models</th>
                            <th>Model Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($universal_parts as $part): ?>
                            <tr>
                                <td>
                                    <code style="background: var(--gray-100); padding: 0.25rem 0.5rem; border-radius: 3px; font-weight: 600;">
                                        <?php echo htmlspecialchars($part['part_number']); ?>
                                    </code>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($part['name']); ?></strong>
                                </td>
                                <td>
                                    <span style="background: var(--primary-color); color: var(--white); padding: 0.25rem 0.75rem; border-radius: var(--border-radius); font-weight: 600; font-size: 0.875rem;">
                                        <?php echo $part['compatible_models']; ?> Models
                                    </span>
                                </td>
                                <td style="font-size: 0.875rem; color: var(--gray-600);">
                                    <?php echo htmlspecialchars($part['models']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Navigation -->
        <div style="text-align: center; margin-top: 2rem;">
            <a href="products.php" class="btn btn-primary">
                <i class="fas fa-cogs"></i> Browse All Products
            </a>
            <a href="categories.php" class="btn btn-secondary">
                <i class="fas fa-th-large"></i> View Categories
            </a>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
