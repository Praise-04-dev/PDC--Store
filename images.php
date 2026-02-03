<?php

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDC Store - Image Path Tester</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 0;
        }
        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .image-test {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        .image-test:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .image-test img {
            max-width: 150px;
            max-height: 150px;
            object-fit: cover;
            border-radius: 8px;
        }
        .status-ok {
            color: #28a745;
            font-weight: bold;
        }
        .status-error {
            color: #dc3545;
            font-weight: bold;
        }
        .status-icon {
            font-size: 24px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">🛍️ PDC Store - Image Path Tester</h1>
        <p class="text-muted">This tool checks if your product images are properly accessible.</p>
        <hr class="mb-4">

        <?php
        // Define your product images
        $images = [
            'camera.webp' => 'Professional Camera',
            'earphone.webp' => 'Wireless Earphones',
            'keyboard.jpg' => 'RGB Gaming Keyboard',
            'laptop.webp' => 'Acer Aspire Lite Laptop',
            'phone.webp' => 'Smartphone Collection',
            'smartwatch.jpeg' => 'Fitness Smartwatch',
            'Speaker.webp' => 'Waterproof Bluetooth Speaker',
            'Tablet.webp' => 'Tablet with Keyboard & Mouse',
            'USB-C_Hub.webp' => 'USB-C Hub Multi-Port Adapter'
        ];

        $base_path = 'images/products/';
        $all_ok = true;

        echo '<div class="row">';
        
        foreach ($images as $filename => $product_name) {
            $image_path = $base_path . $filename;
            $file_exists = file_exists($image_path);
            
            if (!$file_exists) {
                $all_ok = false;
            }
            
            echo '<div class="col-md-6 mb-3">';
            echo '<div class="image-test">';
            echo '<div class="row align-items-center">';
            
            // Image preview
            echo '<div class="col-4 text-center">';
            if ($file_exists) {
                echo '<img src="' . htmlspecialchars($image_path) . '" alt="' . htmlspecialchars($product_name) . '" class="img-fluid">';
            } else {
                echo '<div style="width: 150px; height: 150px; background: #f0f0f0; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #999;">';
                echo '<span>No Image</span>';
                echo '</div>';
            }
            echo '</div>';
            
            // Product info
            echo '<div class="col-8">';
            echo '<h5>' . htmlspecialchars($product_name) . '</h5>';
            echo '<p class="mb-1"><small><code>' . htmlspecialchars($image_path) . '</code></small></p>';
            
            if ($file_exists) {
                $file_size = filesize($image_path);
                $file_size_kb = round($file_size / 1024, 2);
                echo '<p class="status-ok mb-0">';
                echo '<span class="status-icon">✅</span>';
                echo 'File exists (' . $file_size_kb . ' KB)';
                echo '</p>';
            } else {
                echo '<p class="status-error mb-0">';
                echo '<span class="status-icon">❌</span>';
                echo 'File not found!';
                echo '</p>';
                echo '<small class="text-muted">Expected location: ' . realpath('.') . '/' . $image_path . '</small>';
            }
            
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';

        // Summary
        echo '<hr class="my-4">';
        echo '<div class="alert ' . ($all_ok ? 'alert-success' : 'alert-warning') . '">';
        if ($all_ok) {
            echo '<h4>✅ All Images Found!</h4>';
            echo '<p class="mb-0">Your product images are properly set up. You can now proceed to insert the products into your database.</p>';
        } else {
            echo '<h4>⚠️ Some Images Missing!</h4>';
            echo '<p class="mb-2">Please upload the missing images to the <code>images/products/</code> folder.</p>';
            echo '<p class="mb-0"><strong>Current directory:</strong> <code>' . realpath('.') . '</code></p>';
        }
        echo '</div>';

        // Database connection test
        if (file_exists('db.php')) {
            include 'db.php';
            
            echo '<div class="alert alert-info">';
            echo '<h5>📊 Database Status</h5>';
            
            if ($conn->connect_error) {
                echo '<p class="status-error">❌ Database connection failed: ' . htmlspecialchars($conn->connect_error) . '</p>';
            } else {
                echo '<p class="status-ok">✅ Database connected successfully!</p>';
                
                // Check if products table exists
                $table_check = $conn->query("SHOW TABLES LIKE 'products'");
                if ($table_check && $table_check->num_rows > 0) {
                    echo '<p class="status-ok">✅ Products table exists</p>';
                    
                    // Count products
                    $count_result = $conn->query("SELECT COUNT(*) as count FROM products");
                    if ($count_result) {
                        $count = $count_result->fetch_assoc()['count'];
                        echo '<p class="mb-0">📦 Current products in database: <strong>' . $count . '</strong></p>';
                    }
                } else {
                    echo '<p class="status-error">❌ Products table does not exist. Please create it first.</p>';
                }
            }
            echo '</div>';
        }
        ?>

        <div class="mt-4">
            <h5>Next Steps:</h5>
            <ol>
                <li>Ensure all images show ✅ status above</li>
                <li>Create the <code>images/products/</code> folder if it doesn't exist</li>
                <li>Upload all 9 product images to that folder</li>
                <li>Run the SQL script (<code>insert_products.sql</code>) in phpMyAdmin</li>
                <li>Visit <a href="product.php">product.php</a> to see your products</li>
            </ol>
        </div>

        <div class="mt-3">
            <a href="product.php" class="btn btn-primary">Go to Products Page</a>
            <a href="home.html" class="btn btn-outline-secondary">Go to Home</a>
        </div>
    </div>
</body>
</html>