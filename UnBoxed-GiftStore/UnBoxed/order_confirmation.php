<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if order ID is provided
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$order_id = $_GET['id'];

// Fetch order details
$stmt = $pdo->prepare("SELECT o.*, u.full_name, u.email 
                       FROM orders o 
                       JOIN users u ON o.user_id = u.id 
                       WHERE o.id = ? AND o.user_id = ?");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: index.php');
    exit();
}

// Fetch order items
$stmt = $pdo->prepare("SELECT oi.*, p.name as product_name, p.image_url 
                       FROM order_items oi 
                       JOIN products p ON oi.product_id = p.id 
                       WHERE oi.order_id = ?");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - UNBOXED</title>
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
                        <a class="nav-link" href="cart.php">Cart</a>
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
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0">Order Confirmation</h4>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <i class="fas fa-check-circle text-success" style="font-size: 48px;"></i>
                            <h2 class="mt-3">Thank You for Your Order!</h2>
                            <p class="lead">Order #<?php echo $order_id; ?></p>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5>Order Details</h5>
                                <p><strong>Order Date:</strong> <?php echo date('F j, Y', strtotime($order['created_at'])); ?></p>
                                <p><strong>Order Status:</strong> <span class="badge bg-primary"><?php echo ucfirst($order['status']); ?></span></p>
                                <p><strong>Total Amount:</strong> Rs<?php echo number_format($order['total_amount'], 2); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h5>Customer Information</h5>
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($order['full_name']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                            </div>
                        </div>

                        <h5 class="mb-3">Order Items</h5>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($order_items as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if($item['image_url']): ?>
                                                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                                             alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                                             style="width: 50px; margin-right: 10px;">
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars($item['product_name']); ?>
                                                </div>
                                            </td>
                                            <td>Rs<?php echo number_format($item['price'], 2); ?></td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td>Rs<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                            <td><strong>Rs<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="text-center mt-4">
                            <a href="index.php" class="btn btn-primary">Continue Shopping</a>
                            <a href="profile.php" class="btn btn-secondary">View Order History</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white mt-5 py-3">
        <div class="container text-center">
            <p>&copy; 2024 Gift Store. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-font-awesome-kit.js"></script>
</body>
</html> 