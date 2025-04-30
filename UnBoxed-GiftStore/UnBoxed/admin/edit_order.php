<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_order'])) {
    $order_id = $_POST['order_id'];
    $quantities = $_POST['quantities'] ?? [];
    
    try {
        $pdo->beginTransaction();

        // Get current order items
        $stmt = $pdo->prepare("
            SELECT oi.*, p.stock
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order_id]);
        $current_items = $stmt->fetchAll();

        // Calculate total amount
        $total_amount = 0;

        // Update quantities and calculate new total
        foreach ($current_items as $item) {
            $product_id = $item['product_id'];
            $old_quantity = $item['quantity'];
            $new_quantity = isset($quantities[$product_id]) ? (int)$quantities[$product_id] : $old_quantity;

            // Validate new quantity
            if ($new_quantity < 1) {
                throw new Exception("Quantity cannot be less than 1.");
            }

            if ($new_quantity > $item['stock']) {
                throw new Exception("Quantity cannot exceed available stock.");
            }

            // Calculate stock difference
            $stock_diff = $new_quantity - $old_quantity;

            // Update product stock
            $stmt = $pdo->prepare("
                UPDATE products 
                SET stock = stock - ? 
                WHERE id = ?
            ");
            $stmt->execute([$stock_diff, $product_id]);

            // Update order item quantity
            $stmt = $pdo->prepare("
                UPDATE order_items 
                SET quantity = ? 
                WHERE order_id = ? AND product_id = ?
            ");
            $stmt->execute([$new_quantity, $order_id, $product_id]);

            // Add to total amount
            $total_amount += $item['price'] * $new_quantity;
        }

        // Update order total amount
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET total_amount = ? 
            WHERE id = ?
        ");
        $stmt->execute([$total_amount, $order_id]);

        $pdo->commit();
        $_SESSION['success_message'] = "Order updated successfully!";
    } catch(Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error updating order: " . $e->getMessage();
    }
}

// Redirect back to orders page
header('Location: orders.php');
exit(); 