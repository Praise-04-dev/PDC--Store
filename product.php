<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

// Get cart count for the user
$user_id = $_SESSION['user_id'];
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
  <title>PDC Store - Products</title>
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
      background: #dc3545;
      color: white;
      font-size: 11px;
    }

    main {
      padding: 2rem 0;
    }

    main h2 {
      color: #333;
      font-weight: 700;
      margin-bottom: 2rem;
    }

    /* Smaller product images */
    .card-img-top {
      height: 200px;
      object-fit: contain;
      padding: 15px;
      background: #fff;
    }

    .card {
      transition: transform 0.2s, box-shadow 0.2s;
    }

    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
  </style>
</head>
<body>
  <header class="text-white d-flex justify-content-between align-items-center">
    <h1>🛍️ PDC Store</h1>
    <div class="d-flex align-items-center gap-3">
        <a href="order_history.php" class="btn btn-light btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="me-1" viewBox="0 0 16 16">
                <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
            </svg>
            Orders
        </a>
        <a href="cart.php" class="btn btn-light btn-sm cart-badge">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-cart3" viewBox="0 0 16 16">
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

  <main class="container mt-5">
    <h2>Our Products</h2>
    <div class="row">
      <?php
      $sql = "SELECT * FROM products ORDER BY id DESC";
      $result = $conn->query($sql);

      if ($result && $result->num_rows > 0) {
          while($row = $result->fetch_assoc()) {
              $description = isset($row['description']) && !empty($row['description']) 
                  ? '<p class="card-text text-muted small">'.htmlspecialchars($row['description']).'</p>' 
                  : '';
              
              echo '
              <div class="col-md-4 mb-4">
                <div class="card p-3 h-100">
                  <img src="'.htmlspecialchars($row['image']).'" class="card-img-top" alt="'.htmlspecialchars($row['name']).'">
                  <div class="card-body d-flex flex-column">
                    <h5 class="card-title">'.htmlspecialchars($row['name']).'</h5>
                    '.$description.'
                    <p class="card-text text-primary fw-bold fs-5 mt-auto">$'.number_format($row['price'], 2).'</p>
                    
                    <div class="d-flex gap-2">
                        <form action="cart.php" method="POST" class="flex-fill" onsubmit="return validateQuantity(this)">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" value="'.$row['id'].'">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="btn btn-outline-primary w-100">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M9 5.5a.5.5 0 0 0-1 0V7H6.5a.5.5 0 0 0 0 1H8v1.5a.5.5 0 0 0 1 0V8h1.5a.5.5 0 0 0 0-1H9V5.5z"/>
                                    <path d="M.5 1a.5.5 0 0 0 0 1h1.11l.401 1.607 1.498 7.985A.5.5 0 0 0 4 12h1a2 2 0 1 0 0 4 2 2 0 0 0 0-4h7a2 2 0 1 0 0 4 2 2 0 0 0 0-4h1a.5.5 0 0 0 .491-.408l1.5-8A.5.5 0 0 0 14.5 3H2.89l-.405-1.621A.5.5 0 0 0 2 1H.5zm3.915 10L3.102 4h10.796l-1.313 7h-8.17zM6 14a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm7 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                                </svg>
                                Add to Cart
                            </button>
                        </form>
                        
                        <form action="order.php" method="POST" class="flex-fill" onsubmit="return validateQuantity(this)">
                            <input type="hidden" name="product_id" value="'.$row['id'].'">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="btn btn-primary w-100">Buy Now</button>
                        </form>
                    </div>
                  </div>
                </div>
              </div>';
          }
      } else {
          echo "<p>No products found.</p>";
      }
      $conn->close();
      ?>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Validate that quantity is at least 1
    function validateQuantity(form) {
      const quantityInput = form.querySelector('input[name="quantity"]');
      const quantity = parseInt(quantityInput.value);
      
      if (isNaN(quantity) || quantity < 1) {
        alert('Quantity must be at least 1!');
        return false;
      }
      
      return true;
    }
  </script>
</body>
</html>