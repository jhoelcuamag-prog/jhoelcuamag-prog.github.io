<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireAdmin();

$search = $_GET['search'] ?? '';
$query = "SELECT * FROM products WHERE product_name LIKE ? OR product_code LIKE ? ORDER BY id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute(["%$search%", "%$search%"]);
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Lombriks Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f0f2f5; }
        
        .sidebar { width: 260px; background: linear-gradient(180deg, #1a252f 0%, #2c3e50 100%); min-height: 100vh; position: fixed; left: 0; top: 0; z-index: 100; box-shadow: 2px 0 15px rgba(0,0,0,0.1); }
        .sidebar .sidebar-logo { text-align: center; padding: 20px 0 15px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .sidebar .sidebar-logo img { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid rgba(255,255,255,0.2); transition: all 0.3s; }
        .sidebar .sidebar-logo img:hover { transform: scale(1.08); border-color: #667eea; box-shadow: 0 0 25px rgba(102,126,234,0.3); }
        .sidebar .sidebar-logo h5 { color: white; margin-top: 10px; font-weight: 700; letter-spacing: 2px; font-size: 18px; }
        .sidebar .sidebar-logo p { color: rgba(255,255,255,0.5); font-size: 12px; margin-bottom: 0; }
        .sidebar .nav-link { color: rgba(255,255,255,0.7); padding: 12px 20px; margin: 3px 8px; border-radius: 10px; transition: all 0.3s; font-size: 14px; }
        .sidebar .nav-link:hover { background: rgba(255,255,255,0.08); transform: translateX(5px); color: white; }
        .sidebar .nav-link.active { background: linear-gradient(90deg, #667eea, #764ba2); color: white; box-shadow: 0 5px 15px rgba(102,126,234,0.3); }
        .sidebar .nav-link i { margin-right: 12px; width: 20px; font-size: 16px; }
        .sidebar-title { font-size: 11px; color: rgba(255,255,255,0.3); margin: 20px 20px 10px; text-transform: uppercase; letter-spacing: 2px; font-weight: 600; }
        
        .main-content { margin-left: 260px; padding: 20px; }
        .card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
        .btn-primary { background: linear-gradient(90deg, #667eea, #764ba2); border: none; border-radius: 20px; padding: 8px 20px; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102,126,234,0.4); }
        .btn-warning, .btn-danger { border-radius: 8px; padding: 5px 10px; margin: 0 2px; }
        .product-img { width: 60px; height: 60px; object-fit: cover; border-radius: 10px; border: 2px solid #ddd; }
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in { animation: fadeIn 0.5s ease-out; }
        .table th { background: #f8f9fa; font-weight: 600; }
        .table td { vertical-align: middle; }
        
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar .sidebar-logo h5, .sidebar .sidebar-logo p, .sidebar-title { display: none; }
            .sidebar .nav-link span { display: none; }
            .sidebar .nav-link i { margin-right: 0; font-size: 20px; }
            .main-content { margin-left: 70px; }
        }
    </style>
</head>
<body>
    <!-- ===== SIDEBAR WITH LOGO ===== -->
    <div class="sidebar">
        <div class="sidebar-logo">
            <img src="../uploads/logo.jpg" alt="Lombriks Logo" onerror="this.src='https://cdn-icons-png.flaticon.com/512/2997/2997929.png'">
            <h5>LOMBRIKS</h5>
            <p>Inventory System</p>
        </div>
        
        <div class="sidebar-title">MAIN NAVIGATION</div>
        <nav class="nav flex-column">
            <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
            <a class="nav-link active" href="products.php"><i class="fas fa-boxes"></i> <span>Products</span></a>
            <a class="nav-link" href="stock_in.php"><i class="fas fa-arrow-down"></i> <span>Stock In</span></a>
            <a class="nav-link" href="stock_out.php"><i class="fas fa-arrow-up"></i> <span>Stock Out</span></a>
        </nav>
        
        <div class="sidebar-title">FINANCIAL</div>
        <nav class="nav flex-column">
            <a class="nav-link" href="financial.php"><i class="fas fa-chart-line"></i> <span>Financial Management</span></a>
            <a class="nav-link" href="add_income.php"><i class="fas fa-plus-circle"></i> <span>Add Income</span></a>
            <a class="nav-link" href="add_expense.php"><i class="fas fa-minus-circle"></i> <span>Add Expense</span></a>
            <a class="nav-link" href="financial_reports.php"><i class="fas fa-file-alt"></i> <span>Financial Reports</span></a>
        </nav>
        
        <div class="sidebar-title">MANAGEMENT</div>
        <nav class="nav flex-column">
            <a class="nav-link" href="users.php"><i class="fas fa-users"></i> <span>User Management</span></a>
            <a class="nav-link" href="bundle_reports.php"><i class="fas fa-chart-pie"></i> <span>Bundle Reports</span></a>
        </nav>
        
        <div class="sidebar-title">REPORTS & ANALYTICS</div>
        <nav class="nav flex-column">
            <a class="nav-link" href="transactions.php"><i class="fas fa-history"></i> <span>Transactions</span></a>
            <a class="nav-link" href="reports.php"><i class="fas fa-chart-bar"></i> <span>Inventory Reports</span></a>
            <a class="nav-link" href="low_stock.php"><i class="fas fa-exclamation-triangle"></i> <span>Low Stock Alert</span></a>
        </nav>
        
        <div class="sidebar-title">ACCOUNT</div>
        <nav class="nav flex-column">
            <a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </nav>
        
        <div class="text-center mt-4 mb-3">
            <small class="text-white-50"><i class="fas fa-circle" style="font-size: 8px; color: #2ecc71;"></i> System Online</small>
        </div>
    </div>
    
    <div class="main-content fade-in">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-boxes text-primary"></i> Product Management</h2>
            <a href="add_product.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Product</a>
        </div>
        
        <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> 
                <?php 
                    if($_GET['success'] == 'added') echo "Product added successfully!";
                    if($_GET['success'] == 'updated') echo "Product updated successfully!";
                    if($_GET['success'] == 'deleted') echo "Product deleted successfully!";
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-10">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" name="search" class="form-control" placeholder="Search by product name or code..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Search</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Code</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($products) > 0): ?>
                                <?php foreach($products as $product): ?>
                                <tr>
                                    <td>
                                        <?php 
                                        $image_path = '../uploads/products/' . $product['product_image'];
                                        if(!empty($product['product_image']) && $product['product_image'] != 'default-product.png' && file_exists($image_path)) {
                                            echo '<img src="' . $image_path . '" class="product-img" alt="' . htmlspecialchars($product['product_name']) . '">';
                                        } else {
                                            if($product['category'] == 'Version 1') {
                                                echo '<img src="../uploads/489436903_1178033011149547_7631941946891322003_n.jpg" class="product-img" alt="Version 1">';
                                            } elseif($product['category'] == 'Version 2') {
                                                echo '<img src="../uploads/500798185_17850925617462939_7473171847497495972_n.jpg" class="product-img" alt="Version 2">';
                                            } else {
                                                echo '<img src="https://via.placeholder.com/60/667eea/white?text=' . urlencode(substr($product['product_name'], 0, 2)) . '" class="product-img" alt="Product">';
                                            }
                                        }
                                        ?>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($product['product_code']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                    <td><span class="badge <?php echo $product['category'] == 'Version 1' ? 'bg-primary' : 'bg-info'; ?>"><?php echo htmlspecialchars($product['category']); ?></span></td>
                                    <td><span class="badge <?php echo $product['quantity'] <= $product['reorder_level'] ? 'bg-danger' : 'bg-success'; ?>"><?php echo $product['quantity']; ?></span></td>
                                    <td>₱<?php echo number_format($product['unit_price'], 2); ?></td>
                                    <td>
                                        <?php if($product['quantity'] <= $product['reorder_level']): ?>
                                            <span class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle"></i> Low Stock</span>
                                        <?php else: ?>
                                            <span class="badge bg-success"><i class="fas fa-check-circle"></i> In Stock</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-warning" title="Edit"><i class="fas fa-edit"></i></a>
                                        <a href="delete_product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this product?')"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                        <h5>No products found</h5>
                                        <p class="text-muted">Click "Add New Product" to create your first product.</p>
                                        <a href="add_product.php" class="btn btn-primary mt-2">Add New Product</a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>