<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Function to get low stock products
function getLowStockProducts($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE quantity <= reorder_level ORDER BY quantity ASC");
    $stmt->execute();
    return $stmt->fetchAll();
}

$lowStockProducts = getLowStockProducts($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Low Stock Alert - Lombriks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f0f2f5; }
        .sidebar { width: 260px; background: linear-gradient(180deg, #2c3e50 0%, #1a252f 100%); min-height: 100vh; position: fixed; left: 0; top: 0; }
        .sidebar .nav-link { color: #ecf0f1; padding: 12px 20px; margin: 5px 0; border-radius: 10px; transition: all 0.3s; }
        .sidebar .nav-link:hover { background: rgba(255,255,255,0.1); transform: translateX(5px); }
        .sidebar .nav-link.active { background: linear-gradient(90deg, #667eea, #764ba2); }
        .sidebar .nav-link i { margin-right: 10px; width: 20px; }
        .main-content { margin-left: 260px; padding: 20px; }
        .card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); transition: transform 0.3s; }
        .card:hover { transform: translateY(-5px); }
        .btn-primary { background: linear-gradient(90deg, #667eea, #764ba2); border: none; border-radius: 20px; padding: 8px 20px; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102,126,234,0.4); }
        .btn-success { background: linear-gradient(90deg, #43e97b, #38f9d7); border: none; border-radius: 20px; }
        .btn-success:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(67,233,123,0.4); }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in { animation: fadeIn 0.5s ease-out; }
        .low-stock-card {
            border-left: 4px solid #f39c12;
            transition: transform 0.3s;
        }
        .low-stock-card:hover {
            transform: translateX(5px);
        }
        .critical-stock {
            border-left-color: #e74c3c;
        }
        .product-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 10px;
        }
        .stat-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="text-center py-4">
            <img src="https://cdn-icons-png.flaticon.com/512/2997/2997929.png" width="60" alt="Logo">
            <h5 class="text-white mt-2">Lombriks</h5>
            <p class="text-white-50 small"><?php echo isset($_SESSION['role']) ? ucfirst($_SESSION['role']) : 'Guest'; ?> Panel</p>
        </div>
        <nav class="nav flex-column px-3">
            <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a class="nav-link" href="products.php"><i class="fas fa-boxes"></i> Products</a>
            <a class="nav-link" href="stock_in.php"><i class="fas fa-arrow-down"></i> Stock In</a>
            <a class="nav-link" href="stock_out.php"><i class="fas fa-arrow-up"></i> Stock Out</a>
            <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
            <a class="nav-link" href="users.php"><i class="fas fa-users"></i> Users</a>
            <?php endif; ?>
            <a class="nav-link" href="transactions.php"><i class="fas fa-history"></i> Transactions</a>
            <a class="nav-link" href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
            <a class="nav-link active" href="low_stock.php"><i class="fas fa-exclamation-triangle"></i> Low Stock</a>
            <a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>
    
    <div class="main-content fade-in">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-exclamation-triangle text-warning"></i> Low Stock Alert</h2>
            <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
            <a href="stock_in.php" class="btn btn-primary"><i class="fas fa-arrow-down"></i> Restock Now</a>
            <?php endif; ?>
        </div>
        
        <?php if(count($lowStockProducts) > 0): ?>
            <div class="alert alert-warning mb-4">
                <i class="fas fa-bell"></i> There are <strong><?php echo count($lowStockProducts); ?></strong> products that need your attention!
            </div>
            
            <div class="row">
                <?php foreach($lowStockProducts as $product): ?>
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card low-stock-card <?php echo $product['quantity'] <= 5 ? 'critical-stock' : ''; ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="card-title"><?php echo htmlspecialchars($product['product_name']); ?></h5>
                                    <p class="text-muted mb-1">Code: <?php echo htmlspecialchars($product['product_code']); ?></p>
                                    <p class="text-muted mb-1">Category: <?php echo htmlspecialchars($product['category']); ?></p>
                                </div>
                                <i class="fas fa-box fa-2x text-warning"></i>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted">Current Stock</small>
                                    <h4 class="text-danger"><?php echo $product['quantity']; ?></h4>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Reorder Level</small>
                                    <h4><?php echo $product['reorder_level']; ?></h4>
                                </div>
                            </div>
                            <div class="progress mb-3" style="height: 8px;">
                                <?php 
                                $percentage = ($product['quantity'] / $product['reorder_level']) * 100;
                                $percentage = min(100, max(0, $percentage));
                                ?>
                                <div class="progress-bar bg-danger" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                            <div class="d-grid gap-2">
                                <a href="stock_in.php" class="btn btn-sm btn-success">
                                    <i class="fas fa-arrow-down"></i> Restock
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-check-circle fa-5x text-success mb-3"></i>
                    <h3>No Low Stock Items</h3>
                    <p class="text-muted">All products are above their reorder level. Great job!</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>