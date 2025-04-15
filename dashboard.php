<?php
session_start();
require 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user information
$stmt = $pdo->prepare("SELECT name, email, role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get recent orders with user information
$orders = $pdo->query("
    SELECT o.id, o.total_amount, o.status, o.created_at, u.name as customer_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 5
")->fetchAll();

// Get menu items (example query, adjust based on your database structure)
$menu_items = $pdo->query("SELECT * FROM menu_items WHERE status = 'active'")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Cafeteria System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .dashboard-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        .card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            margin-bottom: 20px;
            border: none;
        }
        .card-header {
            background: transparent;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            padding: 15px 20px;
        }
        .card-body {
            padding: 20px;
        }
        .user-info {
            background: #3498db;
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
        }
        .stats-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            transition: all 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .stats-card i {
            font-size: 2rem;
            margin-bottom: 10px;
            color: #3498db;
        }
        .table {
            margin-bottom: 0;
        }
        .table th {
            border-top: none;
            color: #6c757d;
        }
        .btn-primary {
            background-color: #3498db;
            border: none;
            border-radius: 8px;
            padding: 8px 15px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }
        .nav-link {
            color: #6c757d;
            font-weight: 500;
        }
        .nav-link:hover {
            color: #3498db;
        }
        .nav-link.active {
            color: #3498db;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- User Info Section -->
        <div class="user-info">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h3>Welcome, <?php echo htmlspecialchars($user['name']); ?></h3>
                    <p class="mb-0"><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
                <div class="col-md-6 text-end">
                    <a href="logout.php" class="btn btn-light">Logout</a>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <i class="fas fa-utensils"></i>
                    <h4>Total Orders</h4>
                    <h3>150</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <i class="fas fa-users"></i>
                    <h4>Active Users</h4>
                    <h3>45</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <i class="fas fa-coffee"></i>
                    <h4>Menu Items</h4>
                    <h3>25</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <i class="fas fa-dollar-sign"></i>
                    <h4>Today's Revenue</h4>
                    <h3>$1,250</h3>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="row">
            <!-- Recent Orders -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Orders</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Items</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($order['id']); ?></td>
                                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                        <td>
                                            <?php 
                                            // Get order items
                                            $order_items = $pdo->prepare("
                                                SELECT oi.quantity, m.name 
                                                FROM order_items oi 
                                                JOIN menu_items m ON oi.menu_item_id = m.id 
                                                WHERE oi.order_id = ?
                                            ");
                                            $order_items->execute([$order['id']]);
                                            $items = $order_items->fetchAll();
                                            
                                            $item_list = [];
                                            foreach ($items as $item) {
                                                $item_list[] = $item['quantity'] . 'x ' . $item['name'];
                                            }
                                            echo htmlspecialchars(implode(', ', $item_list));
                                            ?>
                                        </td>
                                        <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $order['status'] == 'completed' ? 'success' : 
                                                    ($order['status'] == 'pending' ? 'warning' : 'danger'); 
                                            ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="new_order.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> New Order
                            </a>
                            <a href="manage_menu.php" class="btn btn-primary">
                                <i class="fas fa-edit"></i> Manage Menu
                            </a>
                            <a href="manage_users.php" class="btn btn-primary">
                                <i class="fas fa-users"></i> Manage Users
                            </a>
                            <a href="reports.php" class="btn btn-primary">
                                <i class="fas fa-chart-bar"></i> View Reports
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Menu Items -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Popular Items</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <?php foreach ($menu_items as $item): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?php echo htmlspecialchars($item['name']); ?>
                                <span class="badge bg-primary rounded-pill">$<?php echo number_format($item['price'], 2); ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
