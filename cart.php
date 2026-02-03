<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle cart actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // Add to cart
        if ($action == 'add' && isset($_POST['product_id'])) {
            $product_id = intval($_POST['product_id']);
            $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
            
            $check_sql = "SELECT * FROM cart WHERE user_id = ? AND product_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("ii", $user_id, $product_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                $update_sql = "UPDATE cart SET quantity = quantity + ? WHERE user_id = ? AND product_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("iii", $quantity, $user_id, $product_id);
                $update_stmt->execute();
                $update_stmt->close();
            } else {
                $insert_sql = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("iii", $user_id, $product_id, $quantity);
                $insert_stmt->execute();
                $insert_stmt->close();
            }
            $check_stmt->close();
            
            echo "<script>alert('Added to cart!'); window.location.href='cart.php';</script>";
            exit();
        }
        
        
        // Update quantity
        if ($action == 'update' && isset($_POST['cart_id']) && isset($_POST['quantity'])) {
            $cart_id = intval($_POST['cart_id']);
            $quantity = intval($_POST['quantity']);
            
            // Ensure quantity is at least 1
            if ($quantity < 1) {
                $quantity = 1; // Set to minimum of 1
            }
            
            $update_sql = "UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("iii", $quantity, $cart_id, $user_id);
            $update_stmt->execute();
            $update_stmt->close();
            
            header("Location: cart.php");
            exit();
        }
        
        // Remove from cart
        if ($action == 'remove' && isset($_POST['cart_id'])) {
            $cart_id = intval($_POST['cart_id']);
            $delete_sql = "DELETE FROM cart WHERE id = ? AND user_id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("ii", $cart_id, $user_id);
            $delete_stmt->execute();
            $delete_stmt->close();
            
            header("Location: cart.php");
            exit();
        }
        
        // Clear entire cart
        if ($action == 'clear') {
            $clear_sql = "DELETE FROM cart WHERE user_id = ?";
            $clear_stmt = $conn->prepare($clear_sql);
            $clear_stmt->bind_param("i", $user_id);
            $clear_stmt->execute();
            $clear_stmt->close();
            
            echo "<script>alert('Cart cleared!'); window.location.href='cart.php';</script>";
            exit();
        }
        
        // Checkout - move cart items to orders
        if ($action == 'checkout') {
            $cart_sql = "SELECT * FROM cart WHERE user_id = ?";
            $cart_stmt = $conn->prepare($cart_sql);
            $cart_stmt->bind_param("i", $user_id);
            $cart_stmt->execute();
            $cart_result = $cart_stmt->get_result();
            
            if ($cart_result->num_rows > 0) {
                while ($item = $cart_result->fetch_assoc()) {
                    $order_sql = "INSERT INTO orders (user_id, product_id, quantity) VALUES (?, ?, ?)";
                    $order_stmt = $conn->prepare($order_sql);
                    $order_stmt->bind_param("iii", $user_id, $item['product_id'], $item['quantity']);
                    $order_stmt->execute();
                    $order_stmt->close();
                }
                
                $clear_sql = "DELETE FROM cart WHERE user_id = ?";
                $clear_stmt = $conn->prepare($clear_sql);
                $clear_stmt->bind_param("i", $user_id);
                $clear_stmt->execute();
                $clear_stmt->close();
                
                $cart_stmt->close();
                
                echo "<script>alert('Order placed successfully!'); window.location.href='product.php';</script>";
                exit();
            } else {
                $cart_stmt->close();
                echo "<script>alert('Your cart is empty!'); window.location.href='cart.php';</script>";
                exit();
            }
        }
    }
}

// Get cart items with product details
$sql = "SELECT c.id as cart_id, c.quantity, p.id as product_id, p.name, p.price, p.image
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?
        ORDER BY c.id DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$cart_items = [];
$total = 0;

while ($row = $result->fetch_assoc()) {
    $cart_items[] = $row;
    $total += $row['price'] * $row['quantity'];
}
$stmt->close();

$cart_count = array_sum(array_column($cart_items, 'quantity'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - PDC Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background: #f8f9fa;
        }

        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            padding: 1rem 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        header h1 {
            color: white !important;
            -webkit-text-fill-color: white !important;
            font-size: 1.75rem;
            font-weight: 800;
            margin: 0;
        }

        .cart-item {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .cart-summary {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            position: sticky;
            top: 20px;
        }

        .cart-summary h4 {
            color: #333;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .quantity-input {
            width: 80px;
            text-align: center;
        }

        .empty-cart {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-cart svg {
            opacity: 0.3;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <header class="text-white d-flex justify-content-between align-items-center">
        <h1>🛍️ PDC Store</h1>
        <div>
            <a href="order_history.php" class="btn btn-light btn-sm me-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="me-1" viewBox="0 0 16 16">
                    <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                    <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
                </svg>
                My Orders
            </a>
            <a href="product.php" class="btn btn-light btn-sm me-2">Continue Shopping</a>
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['gmail'] ?? 'User'); ?></span> | 
            <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>
    </header>

    <main class="container mt-5 mb-5">
        <h2 class="mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-cart3 me-2" viewBox="0 0 16 16">
                <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .49.598l-1 5a.5.5 0 0 1-.465.401l-9.397.472L4.415 11H13a.5.5 0 0 1 0 1H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5zM3.102 4l.84 4.479 9.144-.459L13.89 4H3.102zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
            </svg>
            Shopping Cart
        </h2>

        <?php if (count($cart_items) > 0): ?>
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="cart-item">
                                    <div class="row align-items-center">
                                        <div class="col-md-2">
                                            <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                                 class="img-fluid rounded">
                                        </div>
                                        <div class="col-md-4">
                                            <h5><?php echo htmlspecialchars($item['name']); ?></h5>
                                            <p class="text-muted small mb-0">Product ID: #<?php echo $item['product_id']; ?></p>
                                        </div>
                                        <div class="col-md-2 text-center">
                                            <strong>$<?php echo number_format($item['price'], 2); ?></strong>
                                        </div>
                                        <div class="col-md-2">
                                            <form method="POST" class="d-flex align-items-center">
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                                <input type="number" 
                                                       name="quantity" 
                                                       value="<?php echo $item['quantity']; ?>" 
                                                       min="1" 
                                                       class="form-control quantity-input"
                                                       onchange="this.form.submit()">
                                            </form>
                                        </div>
                                        <div class="col-md-2 text-end">
                                            <strong class="text-primary">
                                                $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                            </strong>
                                            <form method="POST" class="d-inline ms-2">
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                        onclick="return confirm('Remove this item?')">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                        <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                                                        <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="mt-3">
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="clear">
                                    <button type="submit" class="btn btn-outline-secondary" 
                                            onclick="return confirm('Clear entire cart?')">
                                        Clear Cart
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="cart-summary">
                        <h4>Order Summary</h4>
                        <hr>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Items (<?php echo count($cart_items); ?>):</span>
                            <span>$<?php echo number_format($total, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping:</span>
                            <span class="text-success">FREE</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total:</strong>
                            <strong class="text-primary fs-4">$<?php echo number_format($total, 2); ?></strong>
                        </div>
                        
                        <form method="POST">
                            <input type="hidden" name="action" value="checkout">
                            <button type="submit" class="btn btn-primary w-100 mb-2" 
                                    onclick="return confirm('Place order for $<?php echo number_format($total, 2); ?>?')">
                                Proceed to Checkout
                            </button>
                        </form>
                        
                        <a href="product.php" class="btn btn-outline-secondary w-100">
                            Continue Shopping
                        </a>
                        
                        <div class="mt-3 small text-muted">
                            <p class="mb-1">✓ Secure checkout</p>
                            <p class="mb-1">✓ Free shipping on all orders</p>
                            <p class="mb-0">✓ 30-day money back guarantee</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-cart">
                <svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" fill="currentColor" class="bi bi-cart-x mb-4" viewBox="0 0 16 16">
                    <path d="M7.354 5.646a.5.5 0 1 0-.708.708L7.793 7.5 6.646 8.646a.5.5 0 1 0 .708.708L8.5 8.207l1.146 1.147a.5.5 0 0 0 .708-.708L9.207 7.5l1.147-1.146a.5.5 0 0 0-.708-.708L8.5 6.793 7.354 5.646z"/>
                    <path d="M.5 1a.5.5 0 0 0 0 1h1.11l.401 1.607 1.498 7.985A.5.5 0 0 0 4 12h1a2 2 0 1 0 0 4 2 2 0 0 0 0-4h7a2 2 0 1 0 0 4 2 2 0 0 0 0-4h1a.5.5 0 0 0 .491-.408l1.5-8A.5.5 0 0 0 14.5 3H2.89l-.405-1.621A.5.5 0 0 0 2 1H.5zm3.915 10L3.102 4h10.796l-1.313 7h-8.17zM6 14a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm7 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                </svg>
                <h3>Your cart is empty</h3>
                <p class="text-muted">Add some products to get started!</p>
                <a href="product.php" class="btn btn-primary mt-3">Browse Products</a>
            </div>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>