<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.html");
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);

    // Validate inputs
    if ($product_id <= 0 || $quantity <= 0) {
        echo "<script>alert('Invalid product or quantity!'); window.location.href='product.php';</script>";
        exit();
    }

    // Get the product name for the success message
    $product_query = "SELECT name FROM products WHERE id = ?";
    $p_stmt = $conn->prepare($product_query);
    $p_stmt->bind_param("i", $product_id);
    $p_stmt->execute();
    $p_result = $p_stmt->get_result();
    
    if ($p_result->num_rows == 0) {
        echo "<script>alert('Product not found!'); window.location.href='product.php';</script>";
        exit();
    }
    
    $product = $p_result->fetch_assoc();
    $product_name = htmlspecialchars($product['name']);
    $p_stmt->close();

    // Insert the order into the database
    $sql = "INSERT INTO orders (user_id, product_id, quantity) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $user_id, $product_id, $quantity);

    if ($stmt->execute()) {
        // Show a nice success message
        echo "<script>
                alert('Success! You ordered $quantity x $product_name.');
                window.location.href = 'product.php';
              </script>";
        exit();
    } else {
        echo "<script>alert('Error placing order. Please try again.'); window.location.href='product.php';</script>";
    }
    $stmt->close();
}
$conn->close();
?>