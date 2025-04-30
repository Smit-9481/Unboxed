<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Check if product ID is provided
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$product_id = $_GET['id'];

try {
    // First, get the product details to delete the image
    $stmt = $pdo->prepare("SELECT image_url FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    // Delete the product
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$product_id]);

    // Delete the product image if it exists
    if ($product && $product['image_url'] && file_exists("../" . $product['image_url'])) {
        unlink("../" . $product['image_url']);
    }

    header('Location: index.php');
    exit();
} catch(PDOException $e) {
    die("Error deleting product: " . $e->getMessage());
} 