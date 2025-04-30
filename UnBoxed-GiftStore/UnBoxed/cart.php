<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle checkout
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['checkout'])) {
    if (empty($_SESSION['cart'])) {
        $error_message = "Your cart is empty.";
    } else {
        try {
            $pdo->beginTransaction();

            // Calculate total amount
            $total_amount = 0;
            foreach ($_SESSION['cart'] as $product_id => $quantity) {
                $stmt = $pdo->prepare("SELECT price, stock FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch();
                
                if ($product['stock'] < $quantity) {
                    throw new Exception("Some items are out of stock.");
                }
                
                $total_amount += $product['price'] * $quantity;
            }

            // Create order
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount) VALUES (?, ?)");
            $stmt->execute([$user_id, $total_amount]);
            $order_id = $pdo->lastInsertId();

            // Add order items and update stock
            foreach ($_SESSION['cart'] as $product_id => $quantity) {
                $stmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch();

                // Add order item
                $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$order_id, $product_id, $quantity, $product['price']]);

                // Update stock
                $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $stmt->execute([$quantity, $product_id]);
            }

            $pdo->commit();
            $success_message = "Order placed successfully!";
            $_SESSION['cart'] = []; // Clear cart
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = $e->getMessage();
        }
    }
}

// Fetch cart items
$cart_items = [];
$total = 0;

if (!empty($_SESSION['cart'])) {
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
    
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($product_ids);
    $products = $stmt->fetchAll();

    foreach ($products as $product) {
        $quantity = $_SESSION['cart'][$product['id']];
        $subtotal = $product['price'] * $quantity;
        $total += $subtotal;
        
        $cart_items[] = [
            'product' => $product,
            'quantity' => $quantity,
            'subtotal' => $subtotal
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart -  UNBOXED</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">UNBOXED</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <?php
                    $stmt = $pdo->query("SELECT * FROM categories");
                    while ($category = $stmt->fetch()) {
                        echo '<li class="nav-item">';
                        echo '<a class="nav-link" href="category.php?id=' . $category['id'] . '">';
                        echo htmlspecialchars($category['name']);
                        echo '</a></li>';
                    }
                    ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="cart.php">Cart</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1>Shopping Cart</h1>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if (empty($cart_items)): ?>
            <div class="alert alert-info">
                Your cart is empty. <a href="index.php">Continue shopping</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items as $item): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo htmlspecialchars($item['product']['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['product']['name']); ?>"
                                             class="me-2" style="width: 50px; height: 50px; object-fit: cover;">
                                        <?php echo htmlspecialchars($item['product']['name']); ?>
                                    </div>
                                </td>
                                <td>Rs<?php echo number_format($item['product']['price'], 2); ?></td>
                                <td>
                                    <form method="POST" action="update_cart.php" class="d-inline">
                                        <input type="hidden" name="product_id" value="<?php echo $item['product']['id']; ?>">
                                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                               min="1" max="<?php echo $item['product']['stock']; ?>" 
                                               class="form-control form-control-sm d-inline-block" style="width: 80px;"
                                               onchange="this.form.submit()">
                                    </form>
                                </td>
                                <td>$<?php echo number_format($item['subtotal'], 2); ?></td>
                                <td>
                                    <form method="POST" action="remove_from_cart.php" class="d-inline">
                                        <input type="hidden" name="product_id" value="<?php echo $item['product']['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Total:</strong></td>
                            <td><strong>Rs<?php echo number_format($total, 2); ?></strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-4">
                <a href="index.php" class="btn btn-secondary">Continue Shopping</a>
                <form method="POST">
                    <button type="submit" name="checkout" class="btn btn-primary">Proceed to Checkout</button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <footer class="bg-dark text-white mt-5 py-3">
        <div class="container text-center">
            <p>&copy; 2024 Gift Store. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 