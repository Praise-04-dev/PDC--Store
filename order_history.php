<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user's order history with product details
$sql = "SELECT o.id, o.quantity, o.order_date, p.name, p.price, p.image, p.description,
               (p.price * o.quantity) as total_price
        FROM orders o
        JOIN products p ON o.product_id = p.id
        WHERE o.user_id = ?
        ORDER BY o.order_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
$total_spent = 0;
$total_orders = 0;

while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
    $total_spent += $row['total_price'];
    $total_orders++;
}
$stmt->close();

// Get cart count for header
$cart_count_sql = "SELECT SUM(quantity) as total FROM cart WHERE user_id = ?";
$cart_stmt = $conn->prepare($cart_count_sql);
$cart_stmt->bind_param("i", $user_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();
$cart_data = $cart_result->fetch_assoc();
$cart_count = $cart_data['total'] ? $cart_data['total'] : 0;
$cart_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - PDC Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background: #5243da;
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

        .order-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            margin-bottom: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
            background: white;
        }

        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .order-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 10px 10px 0 0;
        }

        .order-body {
            padding: 20px;
        }

        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .stats-card h3 {
            font-size: 2rem;
            font-weight: bold;
            margin: 0;
        }

        .stats-card p {
            margin: 0;
            opacity: 0.9;
        }

        .order-status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .empty-orders {
            text-align: center;
            padding: 60px 20px;
        }

        .cart-badge {
            position: relative;
            display: inline-block;
        }

        .cart-badge .badge {
            position: absolute;
            top: -8px;
            right: -8px;
            padding: 4px 6px;
            border-radius: 50%;
            background: red;
            color: white;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <header class="text-white d-flex justify-content-between align-items-center">
        <h1>🛍️ PDC Store</h1>
        <div class="d-flex align-items-center gap-3">
            <a href="product.php" class="btn btn-light btn-sm">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="me-1" viewBox="0 0 16 16">
                    <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5zM3.102 4l1.313 7h8.17l1.313-7H3.102zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                </svg>
                Shop
            </a>
            <a href="cart.php" class="btn btn-light btn-sm cart-badge">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .49.598l-1 5a.5.5 0 0 1-.465.401l-9.397.472L4.415 11H13a.5.5 0 0 1 0 1H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5zM3.102 4l.84 4.479 9.144-.459L13.89 4H3.102zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                </svg>
                Cart
                <?php if ($cart_count > 0): ?>
                    <span class="badge"><?php echo $cart_count; ?></span>
                <?php endif; ?>
            </a>
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['gmail'] ?? 'User'); ?></span> | 
            <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>
    </header>

    <main class="container mt-5 mb-5">
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                        <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5zM3.102 4l1.313 7h8.17l1.313-7H3.102zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                    </svg>
                    My Order History
                </h2>
            </div>
        </div>

        <?php if ($total_orders > 0): ?>
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <div class="stats-card">
                        <p>Total Orders</p>
                        <h3><?php echo $total_orders; ?></h3>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="stats-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <p>Total Spent</p>
                        <h3>$<?php echo number_format($total_spent, 2); ?></h3>
                    </div>
                </div>
            </div>

            <!-- Order History List -->
            <div class="row">
                <div class="col-12">
                    <?php foreach ($orders as $order): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div class="row align-items-center">
                                    <div class="col-md-3">
                                        <small>Order ID</small>
                                        <h5 class="mb-0">#<?php echo $order['id']; ?></h5>
                                    </div>
                                    <div class="col-md-3">
                                        <small>Order Date</small>
                                        <p class="mb-0"><?php echo date('M d, Y', strtotime($order['order_date'])); ?></p>
                                    </div>
                                    <div class="col-md-3">
                                        <small>Total Amount</small>
                                        <h5 class="mb-0">$<?php echo number_format($order['total_price'], 2); ?></h5>
                                    </div>
                                    <div class="col-md-3 text-end">
                                        <span class="order-status status-completed">Completed</span>
                                    </div>
                                </div>
                            </div>
                            <div class="order-body">
                                <div class="row align-items-center">
                                    <div class="col-md-2">
                                        <img src="<?php echo htmlspecialchars($order['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($order['name']); ?>" 
                                             class="img-fluid rounded"
                                             style="max-height: 100px; object-fit: cover;">
                                    </div>
                                    <div class="col-md-6">
                                        <h5 class="mb-1"><?php echo htmlspecialchars($order['name']); ?></h5>
                                        <p class="text-muted mb-0">
                                            <?php echo htmlspecialchars(substr($order['description'], 0, 100)); ?>...
                                        </p>
                                    </div>
                                    <div class="col-md-2 text-center">
                                        <small class="text-muted">Quantity</small>
                                        <p class="mb-0"><strong><?php echo $order['quantity']; ?> item(s)</strong></p>
                                    </div>
                                    <div class="col-md-2 text-center">
                                        <small class="text-muted">Unit Price</small>
                                        <p class="mb-0"><strong>$<?php echo number_format($order['price'], 2); ?></strong></p>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-md-12 text-end">
                                        <small class="text-muted me-3">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="me-1" viewBox="0 0 16 16">
                                                <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                                                <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
                                            </svg>
                                            Ordered on <?php echo date('F d, Y \a\t h:i A', strtotime($order['order_date'])); ?>
                                        </small>
                                        <a href="product.php" class="btn btn-outline-primary btn-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="me-1" viewBox="0 0 16 16">
                                                <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                                            </svg>
                                            Order Again
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        <?php else: ?>
            <!-- Empty State -->
            <div class="empty-orders">
                <svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" fill="currentColor" class="text-muted mb-4" viewBox="0 0 16 16">
                    <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5zM3.102 4l1.313 7h8.17l1.313-7H3.102zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                </svg>
                <h3>No Orders Yet</h3>
                <p class="text-muted mb-4">You haven't placed any orders. Start shopping to see your order history here!</p>
                <a href="product.php" class="btn btn-primary btn-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                        <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5zM3.102 4l1.313 7h8.17l1.313-7H3.102zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                    </svg>
                    Start Shopping
                </a>
            </div>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>