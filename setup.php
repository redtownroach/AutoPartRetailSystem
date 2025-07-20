<?php
// Simple setup file to check if everything is working
echo "<h1>Car Parts System Setup Check</h1>";

// Check if config directory exists
if (!is_dir('config')) {
    echo "<p style='color: red;'>❌ Config directory not found. Creating...</p>";
    mkdir('config', 0755, true);
    echo "<p style='color: green;'>✅ Config directory created.</p>";
} else {
    echo "<p style='color: green;'>✅ Config directory exists.</p>";
}

// Check if includes directory exists
if (!is_dir('includes')) {
    echo "<p style='color: red;'>❌ Includes directory not found. Creating...</p>";
    mkdir('includes', 0755, true);
    echo "<p style='color: green;'>✅ Includes directory created.</p>";
} else {
    echo "<p style='color: green;'>✅ Includes directory exists.</p>";
}

// Check if admin directory exists
if (!is_dir('admin')) {
    echo "<p style='color: red;'>❌ Admin directory not found. Creating...</p>";
    mkdir('admin', 0755, true);
    echo "<p style='color: green;'>✅ Admin directory created.</p>";
} else {
    echo "<p style='color: green;'>✅ Admin directory exists.</p>";
}

// Check if assets directory exists
if (!is_dir('assets')) {
    echo "<p style='color: red;'>❌ Assets directory not found. Creating...</p>";
    mkdir('assets', 0755, true);
    mkdir('assets/css', 0755, true);
    mkdir('assets/js', 0755, true);
    mkdir('assets/images', 0755, true);
    echo "<p style='color: green;'>✅ Assets directory created.</p>";
} else {
    echo "<p style='color: green;'>✅ Assets directory exists.</p>";
}

// Check database connection
try {
    if (file_exists('config/database.php')) {
        require_once 'config/database.php';
        $database = new Database();
        $db = $database->getConnection();
        
        if ($db) {
            echo "<p style='color: green;'>✅ Database connection successful.</p>";
            
            // Check if tables exist
            $tables = ['users', 'car_makes', 'car_models', 'categories', 'products', 'product_compatibility', 'orders', 'order_items'];
            $existing_tables = [];
            
            foreach ($tables as $table) {
                $query = "SHOW TABLES LIKE '$table'";
                $stmt = $db->prepare($query);
                $stmt->execute();
                if ($stmt->fetch()) {
                    $existing_tables[] = $table;
                }
            }
            
            if (count($existing_tables) === count($tables)) {
                echo "<p style='color: green;'>✅ All database tables exist.</p>";
                
                // Check if we have products
                $query = "SELECT COUNT(*) FROM products";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $product_count = $stmt->fetchColumn();
                
                echo "<p style='color: blue;'>📊 Products in database: $product_count</p>";
                
            } else {
                echo "<p style='color: orange;'>⚠️ Some database tables are missing. Please run the SQL schema file.</p>";
                echo "<p>Missing tables: " . implode(', ', array_diff($tables, $existing_tables)) . "</p>";
            }
            
        } else {
            echo "<p style='color: red;'>❌ Database connection failed.</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Database config file not found.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>Make sure your database is created and the schema.sql file is imported</li>";
echo "<li>Update database credentials in config/database.php</li>";
echo "<li>Visit <a href='index.php'>index.php</a> to see the homepage</li>";
echo "<li>Visit <a href='admin/dashboard.php'>admin/dashboard.php</a> to access admin panel</li>";
echo "</ol>";

echo "<h3>Demo Login Credentials:</h3>";
echo "<p><strong>Admin:</strong> admin@carparts.co.tz / password</p>";
echo "<p><strong>Customer:</strong> john@example.com / password</p>";
?>
