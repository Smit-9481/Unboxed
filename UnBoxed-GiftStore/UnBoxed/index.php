<?php
session_start();
require_once 'config/database.php';

// Fetch categories
$stmt = $pdo->query("SELECT * FROM categories");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch featured products
$stmt = $pdo->query("SELECT p.*, c.name as category_name 
                     FROM products p 
                     LEFT JOIN categories c ON p.category_id = c.id 
                     ORDER BY p.created_at DESC 
                     LIMIT 8");
$featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UNBOXED - Find the Perfect Gift</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-gift"></i> UNBOXED
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Home</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="categoriesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Categories
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="categoriesDropdown">
                            <?php foreach($categories as $category): ?>
                            <li>
                                <a class="dropdown-item" href="category.php?id=<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </a>
                            </li>
                            <?php endforeach; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="categories.php">View All Categories</a>
                            </li>
                        </ul>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="cart.php">
                                <i class="fas fa-shopping-cart"></i> Cart
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">
                                <i class="fas fa-user"></i> Profile
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">
                                <i class="fas fa-user-plus"></i> Register
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section text-white text-center py-5">
        <div class="container">
            <div class="row align-items-center min-vh-75">
                <div class="col-md-6 text-start">
                    <h1 class="display-4 fw-bold mb-4">Find the Perfect Gift for Every Occasion</h1>
                    <p class="lead mb-4">Discover our curated collection of unique and thoughtful gifts that will make your loved ones smile.</p>
                    <a href="#featured-products" class="btn btn-primary btn-lg">Explore Gifts</a>
                </div>
                <div class="col-md-6">
                    <img src="assets/images/space.jpg" alt="Beautiful Gift" class="img-fluid rounded shadow">
                </div>
            </div>
        </div>
    </div>

    <!-- Featured Products -->
    <section id="featured-products" class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Featured Gifts</h2>
            <div class="row g-4">
                <?php foreach($featured_products as $product): ?>
                <div class="col-md-3">
                    <div class="card h-100 product-card">
                        <?php if($product['image_url']): ?>
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                 class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="card-text text-muted">
                                <small><?php echo htmlspecialchars($product['category_name']); ?></small>
                            </p>
                            <p class="card-text">Rs<?php echo number_format($product['price'], 2); ?></p>
                            <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">View Details</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about-section py-5 bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h2 class="mb-4">About Our Gift Store</h2>
                    <p class="lead">We believe that every gift should tell a story and create lasting memories.</p>
                    <p>Our carefully curated collection features unique and thoughtful gifts for every occasion. From personalized items to handcrafted treasures, we offer something special for everyone on your list.</p>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-check text-primary me-2"></i> Handpicked unique gifts</li>
                        <li><i class="fas fa-check text-primary me-2"></i> Fast and secure delivery</li>
                        <li><i class="fas fa-check text-primary me-2"></i> Excellent customer service</li>
                        <li><i class="fas fa-check text-primary me-2"></i> Satisfaction guaranteed</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <img src="assets/images/space2.jpg" alt="About Our Store" class="img-fluid rounded shadow">
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="text-center">
                        <i class="fas fa-truck fa-3x text-primary mb-3"></i>
                        <h4>Free Shipping</h4>
                        <p>On orders over Rs.50</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <i class="fas fa-undo fa-3x text-primary mb-3"></i>
                        <h4>Easy Returns</h4>
                        <p>30-day return policy</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <i class="fas fa-lock fa-3x text-primary mb-3"></i>
                        <h4>Secure Payment</h4>
                        <p>100% secure checkout</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Newsletter Section -->
    <section class="newsletter-section py-5 bg-dark text-white">
        <div class="container text-center">
            <h3 class="mb-4">Subscribe to Our Newsletter</h3>
            <p class="mb-4">Get updates about new products and special offers!</p>
            <form class="row justify-content-center">
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="email" class="form-control" placeholder="Enter your email">
                        <button class="btn btn-primary" type="submit">Subscribe</button>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>About Us</h5>
                    <p>Your one-stop shop for finding the perfect gift for any occasion.</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white">Home</a></li>
                        <li><a href="#featured-products" class="text-white">Featured Products</a></li>
                        <li><a href="categories.php" class="text-white">Categories</a></li>
                        <li><a href="contact.php" class="text-white">Contact Us</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Connect With Us</h5>
                    <div class="social-links">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-pinterest"></i></a>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p class="mb-0">&copy; 2025 UNBOXED. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 