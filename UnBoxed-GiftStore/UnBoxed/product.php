<?php
session_start();
require_once 'config/database.php';

// Check if product ID is provided
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$product_id = $_GET['id'];

// Fetch product details
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name 
                       FROM products p 
                       LEFT JOIN categories c ON p.category_id = c.id 
                       WHERE p.id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: index.php');
    exit();
}

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = $quantity;
    }
    
    header('Location: cart.php');
    exit();
}

// Function to check if image exists and is valid
function isValidImage($image_url) {
    if (empty($image_url)) {
        return false;
    }
    
    $image_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $image_url;
    if (!file_exists($image_path)) {
        return false;
    }
    
    $image_info = getimagesize($image_path);
    if ($image_info === false) {
        return false;
    }
    
    return true;
}

// Get default image if product image is not valid
$product_image = isValidImage($product['image_url']) ? $product['image_url'] : 'assets/images/no-image.jpg';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - UNBOXED</title>
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
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="cart.php">Cart</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">Profile</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-6">
                <div class="product-image-container">
                <?php if($product['image_url']): ?>
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                 class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <?php endif; ?>
                    <!-- <img src="<?php echo htmlspecialchars($product_image); ?>" 
                         class="img-fluid product-image" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>"> -->
                </div>
            </div>
            <div class="col-md-6">
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                <p class="text-muted">Category: <?php echo htmlspecialchars($product['category_name']); ?></p>
                <p class="lead">Rs<?php echo number_format($product['price'], 2); ?></p>
                <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>

                <?php if($product['stock'] > 0): ?>
                    <form method="POST" class="mt-4">
                        <div class="mb-3">
                            <label for="quantity" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" 
                                    value="1" min="1" max="<?php echo $product['stock']; ?>" style="width: 100px;">
                        </div>
                        <button type="submit" name="add_to_cart" class="btn btn-primary">Add to Cart</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning">This product is currently out of stock.</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Related Products -->
        <div class="mt-5">
            <h2>Related Products</h2>
            <div class="row">
                <?php
                $stmt = $pdo->prepare("SELECT * FROM products WHERE category_id = ? AND id != ? LIMIT 4");
                $stmt->execute([$product['category_id'], $product_id]);
                    $related_products = $stmt->fetchAll();
                    
                    foreach($related_products as $related): 
                        $related_image = isValidImage($related['image_url']) ? $related['image_url'] : 'assets/images/no-image.jpg';
                    ?>
                        <div class="col-md-3">
                            <div class="card product-card">
                            <?php if($product['image_url']): ?>
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                 class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <?php endif; ?>    
                            
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($related['name']); ?></h5>
                                        <p class="card-text">Rs<?php echo number_format($related['price'], 2); ?></p>
                                    <a href="product.php?id=<?php echo $related['id']; ?>" class="btn btn-primary">View Details</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white mt-5 py-3">
        <div class="container text-center">
            <p>&copy; 2024 Gift Store. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 