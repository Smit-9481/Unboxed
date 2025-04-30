<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['product_id']) && isset($_POST['quantity'])) {
    $product_id = $_POST['product_id'];
    $quantity = (int)$_POST['quantity'];

    // Validate quantity
    if ($quantity < 1) {
        header('Location: cart.php');
        exit();
    }

    // Check stock availability
    $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if ($product && $quantity <= $product['stock']) {
        $_SESSION['cart'][$product_id] = $quantity;
    }
}

header('Location: cart.php');
exit(); 