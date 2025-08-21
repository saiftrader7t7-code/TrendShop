<?php
// --- CONFIGURATION ---
$db_host = '127.0.0.1';
$db_user = 'root';
$db_pass = 'root';
$db_name = 'quick_kart_db';
$admin_user = 'admin';
$admin_pass = 'admin123';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Kart Installation</title>
    <style>
        body { 
            font-family: 'Courier New', Courier, monospace; 
            background-color: #1a1a1a; 
            color: #f0f0f0; 
            padding: 20px; 
            font-size: 14px; 
            line-height: 1.7; 
        }
        pre { 
            white-space: pre-wrap; 
            word-wrap: break-word; 
        }
        .success { color: #00e676; }
        .error { color: #ff5252; font-weight: bold; }
        .info { color: #00bcd4; }
        .redirect-box { 
            margin-top: 25px; 
            padding: 15px; 
            background-color: #333; 
            border-left: 5px solid #00e676; 
            border-radius: 4px;
        }
        a { color: #00bcd4; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<pre>
<?php
// Start output buffering to catch any stray output
ob_start();

echo "<span class='info'>🚀 Quick Kart Installation Started...</span>\n\n";

try {
    // 1. Connect to MySQL Server (without selecting DB)
    $conn = new mysqli($db_host, $db_user, $db_pass);
    if ($conn->connect_error) {
        throw new Exception("MySQL Connection Failed: " . $conn->connect_error);
    }
    echo "<span class='success'>✅ MySQL connection successful.</span>\n";

    // 2. Create Database
    $sql_create_db = "CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    if ($conn->query($sql_create_db) === TRUE) {
        echo "<span class='success'>✅ Database '$db_name' created or already exists.</span>\n";
    } else {
        throw new Exception("Error creating database: " . $conn->error);
    }
    
    $conn->select_db($db_name);
    echo "<span class='success'>✅ Selected database '$db_name'.</span>\n";

    // 3. Define and Create Tables
    $sql_tables = [
        "CREATE TABLE IF NOT EXISTS `users` (`id` INT AUTO_INCREMENT PRIMARY KEY, `name` VARCHAR(100) NOT NULL, `phone` VARCHAR(15) NOT NULL UNIQUE, `email` VARCHAR(100) NOT NULL UNIQUE, `password` VARCHAR(255) NOT NULL, `address` TEXT, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB;",
        "CREATE TABLE IF NOT EXISTS `admin` (`id` INT AUTO_INCREMENT PRIMARY KEY, `username` VARCHAR(50) NOT NULL UNIQUE, `password` VARCHAR(255) NOT NULL) ENGINE=InnoDB;",
        "CREATE TABLE IF NOT EXISTS `categories` (`id` INT AUTO_INCREMENT PRIMARY KEY, `name` VARCHAR(100) NOT NULL, `image` VARCHAR(255)) ENGINE=InnoDB;",
        "CREATE TABLE IF NOT EXISTS `products` (`id` INT AUTO_INCREMENT PRIMARY KEY, `cat_id` INT, `name` VARCHAR(255) NOT NULL, `description` TEXT, `price` DECIMAL(10, 2) NOT NULL, `stock` INT NOT NULL DEFAULT 0, `image` VARCHAR(255), `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (`cat_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL) ENGINE=InnoDB;",
        "CREATE TABLE IF NOT EXISTS `orders` (`id` INT AUTO_INCREMENT PRIMARY KEY, `user_id` INT, `total_amount` DECIMAL(10, 2) NOT NULL, `shipping_address` TEXT NOT NULL, `status` VARCHAR(50) DEFAULT 'Placed', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE) ENGINE=InnoDB;",
        "CREATE TABLE IF NOT EXISTS `order_items` (`id` INT AUTO_INCREMENT PRIMARY KEY, `order_id` INT, `product_id` INT, `quantity` INT NOT NULL, `price` DECIMAL(10, 2) NOT NULL, FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE, FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL) ENGINE=InnoDB;"
    ];

    echo "\n<span class='info'>Creating tables...</span>\n";
    foreach ($sql_tables as $query) {
        if ($conn->query($query) === TRUE) {
            preg_match('/CREATE TABLE IF NOT EXISTS `(.*?)`/', $query, $matches);
            $table_name = $matches[1] ?? 'unknown';
            echo "  <span class='success'>✅ Table '$table_name' created successfully.</span>\n";
        } else {
            throw new Exception("Error creating table '$table_name': " . $conn->error);
        }
    }

    // 4. Insert Default Admin
    $hashed_password = password_hash($admin_pass, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO `admin` (username, password) VALUES (?, ?) ON DUPLICATE KEY UPDATE password = VALUES(password)");
    $stmt->bind_param("ss", $admin_user, $hashed_password);
    if($stmt->execute()){
        echo "\n<span class='success'>✅ Default admin user ('$admin_user' / '$admin_pass') created/updated successfully.</span>\n";
    }
    $stmt->close();
    
    // 5. Create Upload Directories
    echo "\n<span class='info'>Creating upload directories...</span>\n";
    $dirs = ['uploads', 'uploads/categories', 'uploads/products'];
    foreach ($dirs as $dir) {
        if (!is_dir(__DIR__ . '/' . $dir)) {
            if (mkdir(__DIR__ . '/' . $dir, 0755, true)) {
                echo "  <span class='success'>✅ Directory '$dir' created.</span>\n";
            } else {
                throw new Exception("Error creating directory '$dir'. Please check folder permissions.");
            }
        } else {
            echo "  <span class='success'>✅ Directory '$dir' already exists.</span>\n";
        }
    }

    $conn->close();

    // Final success message
    echo "\n<div class='redirect-box'>";
    echo "<span class='success'>🎉 INSTALLATION COMPLETE! 🎉</span>\n";
    echo "<span>You will be redirected to the login page in 5 seconds...</span>\n";
    echo "<span>If you are not redirected, <a href='login.php'>click here</a>.</span>";
    echo "</div>";

    // THE FIX: Use JavaScript for redirection instead of PHP header()
    echo "
    <script>
        setTimeout(function() {
            window.location.href = 'login.php';
        }, 5000);
    </script>
    ";

} catch (Exception $e) {
    // If an error occurs, clear the buffer and show the error message
    ob_end_clean();
    echo "<span class='error'>❌ INSTALLATION FAILED: " . $e->getMessage() . "</span>\n";
    echo "<span class='error'>Please fix the issue and try again.</span>\n";
}

// Flush (send) the output buffer to the browser
ob_end_flush();
?>
</pre>
</body>
</html>