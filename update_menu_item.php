<?php
session_start();
require 'includes/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit();
}

// Validate required fields
$required_fields = ['id', 'name', 'description', 'price', 'category'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['error' => "Missing required field: $field"]);
        exit();
    }
}

try {
    $stmt = $pdo->prepare("
        UPDATE menu_items 
        SET name = ?, description = ?, price = ?, category = ?
        WHERE id = ?
    ");
    
    $stmt->execute([
        $_POST['name'],
        $_POST['description'],
        $_POST['price'],
        $_POST['category'],
        $_POST['id']
    ]);

    if ($stmt->rowCount() === 0) {
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['error' => 'Menu item not found']);
        exit();
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Database error occurred']);
} 