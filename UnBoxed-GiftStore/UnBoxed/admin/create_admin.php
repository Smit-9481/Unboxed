<?php
require_once '../config/database.php';

// Check if admin user exists
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = 'admin'");
$stmt->execute();
$admin = $stmt->fetch();

if (!$admin) {
    // Create admin user
    $username = 'admin';
    $password = 'admin123';
    $email = 'admin@example.com';
    $full_name = 'Admin User';
    $is_admin = 1;
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, full_name, is_admin) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$username, $hashed_password, $email, $full_name, $is_admin]);
        echo "Admin user created successfully!<br>";
        echo "Username: admin<br>";
        echo "Password: admin123<br>";
    } catch(PDOException $e) {
        echo "Error creating admin user: " . $e->getMessage();
    }
} else {
    echo "Admin user already exists!<br>";
    echo "Username: admin<br>";
    echo "Password: admin123<br>";
}
?> 