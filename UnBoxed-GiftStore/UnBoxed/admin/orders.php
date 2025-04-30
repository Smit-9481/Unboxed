<?php
session_start();
require_once '../config/database.php';
// require_once '../includes/email_notifications.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Handle order deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_order'])) {
    $order_id = $_POST['order_id'];
    
    try {
        $pdo->beginTransaction();

        // Get order items to restore stock
        $stmt = $pdo->prepare("
            SELECT oi.product_id, oi.quantity
            FROM order_items oi
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order_id]);
        $items = $stmt->fetchAll();

        // Restore stock for all items
        foreach ($items as $item) {
            $stmt = $pdo->prepare("
                UPDATE products 
                SET stock = stock + ? 
                WHERE id = ?
            ");
            $stmt->execute([$item['quantity'], $item['product_id']]);
        }

        // Delete order items
        $stmt = $pdo->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt->execute([$order_id]);

        // Delete order
        $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);

        $pdo->commit();
        $success_message = "Order deleted successfully!";
    } catch(Exception $e) {
        $pdo->rollBack();
        $error = "Error deleting order: " . $e->getMessage();
    }
}

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['order_id']) && isset($_POST['status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    
    try {
        $pdo->beginTransaction();

        // Get current order status
        $stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $current_status = $stmt->fetchColumn();

        if (!$current_status) {
            throw new Exception("Order not found.");
        }

        // Validate status transition
        if ($current_status === 'completed' && $new_status !== 'completed') {
            throw new Exception("Cannot change status of completed orders.");
        }

        if ($current_status === 'cancelled' && $new_status !== 'cancelled') {
            throw new Exception("Cannot change status of cancelled orders.");
        }

        // Update order status
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $order_id]);

        // Handle stock management based on status change
        if ($new_status === 'cancelled' && $current_status !== 'cancelled') {
            // Restore stock for cancelled orders
            $stmt = $pdo->prepare("
                SELECT oi.product_id, oi.quantity
                FROM order_items oi
                WHERE oi.order_id = ?
            ");
            $stmt->execute([$order_id]);
            $items = $stmt->fetchAll();

            foreach ($items as $item) {
                $stmt = $pdo->prepare("
                    UPDATE products 
                    SET stock = stock + ? 
                    WHERE id = ?
                ");
                $stmt->execute([$item['quantity'], $item['product_id']]);
            }
        }

        $pdo->commit();
        $success_message = "Order status updated successfully!";
    } catch(Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// Fetch all orders with user details
$stmt = $pdo->query("
    SELECT o.*, u.username, u.email, u.full_name,
           COUNT(oi.id) as item_count
    FROM orders o
    JOIN users u ON o.user_id = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    GROUP BY o.id
    ORDER BY o.created_at DESC
");
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Manage Orders - UNBOXED</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
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
                        <a class="nav-link" href="index.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="categories.php">Categories</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="orders.php">Orders</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1>Manage Orders</h1>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td>
                                <?php echo htmlspecialchars($order['full_name']); ?><br>
                                <small class="text-muted"><?php echo htmlspecialchars($order['email']); ?></small>
                            </td>
                            <td><?php echo $order['item_count']; ?> items</td>
                            <td>Rs<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo match($order['status']) {
                                        'pending' => 'warning',
                                        'confirmed' => 'info',
                                        'cancelled' => 'danger',
                                        'completed' => 'success',
                                        default => 'secondary'
                                    };
                                ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#orderModal<?php echo $order['id']; ?>">
                                    View Details
                                </button>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <select name="status" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                        <?php if ($order['status'] === 'pending'): ?>
                                            <option value="pending" selected>Pending</option>
                                            <option value="confirmed">Confirm Order</option>
                                            <option value="cancelled">Cancel Order</option>
                                        <?php elseif ($order['status'] === 'confirmed'): ?>
                                            <option value="pending">Pending</option>
                                            <option value="confirmed" selected>Confirmed</option>
                                            <option value="completed">Mark as Completed</option>
                                            <option value="cancelled">Cancel Order</option>
                                        <?php elseif ($order['status'] === 'completed'): ?>
                                            <option value="completed" selected>Completed</option>
                                        <?php elseif ($order['status'] === 'cancelled'): ?>
                                            <option value="cancelled" selected>Cancelled</option>
                                        <?php endif; ?>
                                    </select>
                                </form>
                                <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editOrderModal<?php echo $order['id']; ?>">
                                    Edit
                                </button>
                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteOrderModal<?php echo $order['id']; ?>">
                                    Delete
                                </button>
                            </td>
                        </tr>

                        <!-- Order Details Modal -->
                        <div class="modal fade" id="orderModal<?php echo $order['id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Order #<?php echo $order['id']; ?> Details</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <?php
                                        $stmt = $pdo->prepare("
                                            SELECT oi.*, p.name as product_name, p.image_url, p.stock
                                            FROM order_items oi
                                            JOIN products p ON oi.product_id = p.id
                                            WHERE oi.order_id = ?
                                        ");
                                        $stmt->execute([$order['id']]);
                                        $items = $stmt->fetchAll();
                                        ?>
                                        
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
                                                    <?php foreach ($items as $item): ?>
                                                        <tr>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <img src="../<?php echo htmlspecialchars($item['image_url']); ?>" 
                                                                         alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                                                         class="me-2" style="width: 50px; height: 50px; object-fit: cover;">
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
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Edit Order Modal -->
                        <div class="modal fade" id="editOrderModal<?php echo $order['id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Order #<?php echo $order['id']; ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form method="POST" action="edit_order.php">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <div class="table-responsive">
                                                <table class="table">
                                                    <thead>
                                                        <tr>
                                                            <th>Product</th>
                                                            <th>Price</th>
                                                            <th>Available Stock</th>
                                                            <th>Quantity</th>
                                                            <th>Subtotal</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($items as $item): ?>
                                                            <tr>
                                                                <td>
                                                                    <div class="d-flex align-items-center">
                                                                        <img src="../<?php echo htmlspecialchars($item['image_url']); ?>" 
                                                                             alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                                                             class="me-2" style="width: 50px; height: 50px; object-fit: cover;">
                                                                        <?php echo htmlspecialchars($item['product_name']); ?>
                                                                    </div>
                                                                </td>
                                                                <td>Rs<?php echo number_format($item['price'], 2); ?></td>
                                                                <td><?php echo $item['stock']; ?></td>
                                                                <td>
                                                                    <input type="number" 
                                                                           name="quantities[<?php echo $item['product_id']; ?>]" 
                                                                           value="<?php echo $item['quantity']; ?>"
                                                                           min="1" 
                                                                           max="<?php echo $item['stock']; ?>"
                                                                           class="form-control form-control-sm"
                                                                           style="width: 80px;">
                                                                </td>
                                                                <td>Rs<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                    <tfoot>
                                                        <tr>
                                                            <td colspan="4" class="text-end"><strong>Total:</strong></td>
                                                            <td><strong>Rs<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                            <div class="text-end mt-3">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" name="update_order" class="btn btn-primary">Update Order</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Delete Order Modal -->
                        <div class="modal fade" id="deleteOrderModal<?php echo $order['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Delete Order #<?php echo $order['id']; ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p>Are you sure you want to delete this order? This action cannot be undone.</p>
                                        <p class="text-danger">Note: This will restore the product stock.</p>
                                    </div>
                                    <div class="modal-footer">
                                        <form method="POST">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" name="delete_order" class="btn btn-danger">Delete Order</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 