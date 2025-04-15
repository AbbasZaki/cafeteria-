<?php
session_start();
require 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get menu items
$menu_items = $pdo->query("SELECT * FROM menu_items WHERE status = 'active'")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $items = $_POST['items'];
    $quantities = $_POST['quantities'];
    $total = 0;

    // Calculate total
    foreach ($items as $key => $item_id) {
        $menu_item = $pdo->query("SELECT price FROM menu_items WHERE id = $item_id")->fetch();
        $total += $menu_item['price'] * $quantities[$key];
    }

    // Start transaction
    $pdo->beginTransaction();

    try {
        // Insert order
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, 'pending')");
        $stmt->execute([$user_id, $total]);
        $order_id = $pdo->lastInsertId();

        // Insert order items
        $stmt = $pdo->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity, price) VALUES (?, ?, ?, ?)");
        foreach ($items as $key => $item_id) {
            $menu_item = $pdo->query("SELECT price FROM menu_items WHERE id = $item_id")->fetch();
            $stmt->execute([$order_id, $item_id, $quantities[$key], $menu_item['price']]);
        }

        $pdo->commit();
        $success_message = "Order placed successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = "Error placing order: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Order - Cafeteria System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px 0;
        }
        .order-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .menu-item {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
        }
        .quantity-input {
            width: 60px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container order-container">
        <div class="card">
            <div class="card-header">
                <h3>New Order</h3>
            </div>
            <div class="card-body">
                <?php if(isset($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>
                <?php if(isset($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <form method="POST" id="orderForm">
                    <div id="orderItems">
                        <?php foreach ($menu_items as $item): ?>
                        <div class="menu-item">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h5><?php echo htmlspecialchars($item['name']); ?></h5>
                                    <p class="text-muted"><?php echo htmlspecialchars($item['description']); ?></p>
                                    <p class="text-primary">$<?php echo number_format($item['price'], 2); ?></p>
                                </div>
                                <div class="col-md-6 text-end">
                                    <input type="hidden" name="items[]" value="<?php echo $item['id']; ?>">
                                    <input type="number" name="quantities[]" class="quantity-input" min="0" value="0">
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-primary">Place Order</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 