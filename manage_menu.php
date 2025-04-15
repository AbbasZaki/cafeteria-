<?php
session_start();
require 'includes/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $stmt = $pdo->prepare("INSERT INTO menu_items (name, description, price, category, status) VALUES (?, ?, ?, ?, 'active')");
                $stmt->execute([$_POST['name'], $_POST['description'], $_POST['price'], $_POST['category']]);
                $success_message = "Menu item added successfully!";
                break;
            
            case 'update':
                $stmt = $pdo->prepare("UPDATE menu_items SET name = ?, description = ?, price = ?, category = ? WHERE id = ?");
                $stmt->execute([$_POST['name'], $_POST['description'], $_POST['price'], $_POST['category'], $_POST['id']]);
                $success_message = "Menu item updated successfully!";
                break;
            
            case 'delete':
                $stmt = $pdo->prepare("UPDATE menu_items SET status = 'inactive' WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $success_message = "Menu item deleted successfully!";
                break;
        }
    }
}

// Get all menu items
$menu_items = $pdo->query("SELECT * FROM menu_items ORDER BY category, name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Menu - Cafeteria System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px 0;
        }
        .menu-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .btn-primary {
            background-color: #3498db;
            border: none;
            border-radius: 8px;
            padding: 8px 15px;
            font-weight: 600;
        }
        .btn-danger {
            background-color: #e74c3c;
            border: none;
            border-radius: 8px;
            padding: 8px 15px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="menu-container">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3>Manage Menu</h3>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
            <div class="card-body">
                <?php if(isset($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>

                <!-- Add New Item Form -->
                <div class="mb-4">
                    <h4>Add New Menu Item</h4>
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="action" value="add">
                        <div class="col-md-4">
                            <input type="text" name="name" class="form-control" placeholder="Item Name" required>
                        </div>
                        <div class="col-md-4">
                            <input type="text" name="description" class="form-control" placeholder="Description" required>
                        </div>
                        <div class="col-md-2">
                            <input type="number" name="price" class="form-control" placeholder="Price" step="0.01" required>
                        </div>
                        <div class="col-md-2">
                            <input type="text" name="category" class="form-control" placeholder="Category" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Add Item</button>
                        </div>
                    </form>
                </div>

                <!-- Menu Items Table -->
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Price</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($menu_items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo htmlspecialchars($item['description']); ?></td>
                                <td>$<?php echo number_format($item['price'], 2); ?></td>
                                <td><?php echo htmlspecialchars($item['category']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $item['status'] == 'active' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($item['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="editItem(<?php echo $item['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editItem(id) {
            // Fetch item details
            fetch('get_menu_item.php?id=' + id)
                .then(response => response.json())
                .then(item => {
                    // Populate form fields
                    document.getElementById('edit_id').value = item.id;
                    document.getElementById('edit_name').value = item.name;
                    document.getElementById('edit_description').value = item.description;
                    document.getElementById('edit_price').value = item.price;
                    document.getElementById('edit_category').value = item.category;
                    
                    // Show modal
                    new bootstrap.Modal(document.getElementById('editModal')).show();
                });
        }
    </script>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Menu Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="editForm">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <input type="text" name="description" id="edit_description" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price</label>
                            <input type="number" name="price" id="edit_price" class="form-control" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <input type="text" name="category" id="edit_category" class="form-control" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="editForm" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 