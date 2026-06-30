<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

if($_SESSION['role'] != 'admin') {
    header("Location: ../staff/dashboard.php");
    exit();
}

$stats = getDashboardStats($pdo);
$lowStockProducts = getLowStockProducts($pdo);
$recentTransactions = $pdo->query("SELECT t.*, p.product_name, u.full_name FROM transactions t JOIN products p ON t.product_id = p.id JOIN users u ON t.user_id = u.id ORDER BY t.transaction_date DESC LIMIT 10")->fetchAll();

// Get bundle production summary for dashboard
$totalBatchesUsed = $pdo->query("SELECT COUNT(DISTINCT batch_no) as total FROM bundle_production")->fetch()['total'] ?? 0;
$totalProduction = $pdo->query("SELECT SUM(quantity) as total FROM bundle_production WHERE type = 'production'")->fetch()['total'] ?? 0;

// Get financial summary for dashboard
$today = date('Y-m-d');
$stmt = $pdo->prepare("
    SELECT 
        SUM(CASE WHEN transaction_type = 'income' THEN total_amount ELSE 0 END) as today_income,
        SUM(CASE WHEN transaction_type = 'expense' THEN total_amount ELSE 0 END) as today_expense
    FROM financial_transactions WHERE date = ?
");
$stmt->execute([$today]);
$todayFinancial = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Lombriks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f0f2f5; }
        
        /* ===== SIDEBAR ===== */
        .sidebar { 
            width: 260px; 
            background: linear-gradient(180deg, #1a252f 0%, #2c3e50 100%); 
            min-height: 100vh; 
            position: fixed; 
            left: 0; 
            top: 0; 
            transition: all 0.3s; 
            z-index: 100; 
            box-shadow: 2px 0 15px rgba(0,0,0,0.1);
        }
        .sidebar .sidebar-logo {
            text-align: center;
            padding: 20px 0 15px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .sidebar .sidebar-logo img {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255,255,255,0.2);
            transition: all 0.3s;
        }
        .sidebar .sidebar-logo img:hover {
            transform: scale(1.05);
            border-color: #667eea;
        }
        .sidebar .sidebar-logo h5 {
            color: white;
            margin-top: 10px;
            font-weight: 700;
            letter-spacing: 2px;
            font-size: 18px;
        }
        .sidebar .sidebar-logo p {
            color: rgba(255,255,255,0.5);
            font-size: 12px;
            margin-bottom: 0;
        }
        .sidebar .nav-link { 
            color: rgba(255,255,255,0.7); 
            padding: 12px 20px; 
            margin: 3px 8px; 
            border-radius: 10px; 
            transition: all 0.3s; 
            font-size: 14px;
        }
        .sidebar .nav-link:hover { 
            background: rgba(255,255,255,0.08); 
            transform: translateX(5px); 
            color: white;
        }
        .sidebar .nav-link.active { 
            background: linear-gradient(90deg, #667eea, #764ba2); 
            color: white;
            box-shadow: 0 5px 15px rgba(102,126,234,0.3);
        }
        .sidebar .nav-link i { 
            margin-right: 12px; 
            width: 20px; 
            font-size: 16px;
        }
        .sidebar-title { 
            font-size: 11px; 
            color: rgba(255,255,255,0.3); 
            margin: 20px 20px 10px; 
            text-transform: uppercase; 
            letter-spacing: 2px; 
            font-weight: 600;
        }
        
        /* ===== TOP HEADER ===== */
        .top-header {
            background: white;
            padding: 12px 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .top-header .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .top-header .header-left .header-logo {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #667eea;
        }
        .top-header .header-left h4 {
            margin: 0;
            font-weight: 600;
            color: #2c3e50;
        }
        .top-header .header-left small {
            color: #7f8c8d;
            font-size: 13px;
        }
        .top-header .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .top-header .header-right .user-info {
            text-align: right;
        }
        .top-header .header-right .user-info p {
            margin: 0;
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
        }
        .top-header .header-right .user-info small {
            color: #7f8c8d;
            font-size: 12px;
        }
        .top-header .header-right .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .top-header .header-right .user-avatar:hover {
            transform: scale(1.05);
        }
        
        /* ===== MAIN CONTENT ===== */
        .main-content { margin-left: 260px; padding: 20px; }
        .card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); transition: transform 0.3s; }
        .card:hover { transform: translateY(-5px); }
        .stat-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-primary { background: linear-gradient(90deg, #667eea, #764ba2); border: none; padding: 8px 20px; border-radius: 20px; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102,126,234,0.4); }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in { animation: fadeIn 0.5s ease-out; }
        .financial-mini-card { 
            background: white; 
            border-radius: 12px; 
            padding: 12px; 
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .financial-mini-card .amount { font-size: 18px; font-weight: 700; }
        .financial-mini-card .label { font-size: 11px; color: #7f8c8d; text-transform: uppercase; }
        
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar .sidebar-logo h5, .sidebar .sidebar-logo p, .sidebar-title { display: none; }
            .sidebar .nav-link span { display: none; }
            .sidebar .nav-link i { margin-right: 0; font-size: 20px; }
            .main-content { margin-left: 70px; }
            .top-header { flex-direction: column; gap: 10px; text-align: center; }
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
            <a class="nav-link active" href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
            </a>
            <a class="nav-link" href="products.php">
                <i class="fas fa-boxes"></i> <span>Products</span>
            </a>
            <a class="nav-link" href="stock_in.php">
                <i class="fas fa-arrow-down"></i> <span>Stock In</span>
            </a>
            <a class="nav-link" href="stock_out.php">
                <i class="fas fa-arrow-up"></i> <span>Stock Out</span>
            </a>
        </nav>
        
        <div class="sidebar-title">FINANCIAL</div>
        <nav class="nav flex-column">
            <a class="nav-link" href="financial.php">
                <i class="fas fa-chart-line"></i> <span>Financial Management</span>
            </a>
            <a class="nav-link" href="add_income.php">
                <i class="fas fa-plus-circle"></i> <span>Add Income</span>
            </a>
            <a class="nav-link" href="add_expense.php">
                <i class="fas fa-minus-circle"></i> <span>Add Expense</span>
            </a>
            <a class="nav-link" href="financial_reports.php">
                <i class="fas fa-file-alt"></i> <span>Financial Reports</span>
            </a>
        </nav>
        
        <div class="sidebar-title">MANAGEMENT</div>
        <nav class="nav flex-column">
            <a class="nav-link" href="users.php">
                <i class="fas fa-users"></i> <span>User Management</span>
            </a>
            <a class="nav-link" href="bundle_reports.php">
                <i class="fas fa-chart-pie"></i> <span>Bundle Reports</span>
            </a>
        </nav>
        
        <div class="sidebar-title">REPORTS & ANALYTICS</div>
        <nav class="nav flex-column">
            <a class="nav-link" href="transactions.php">
                <i class="fas fa-history"></i> <span>Transactions</span>
            </a>
            <a class="nav-link" href="reports.php">
                <i class="fas fa-chart-bar"></i> <span>Inventory Reports</span>
            </a>
            <a class="nav-link" href="low_stock.php">
                <i class="fas fa-exclamation-triangle"></i> <span>Low Stock Alert</span>
            </a>
        </nav>
        
        <div class="sidebar-title">ACCOUNT</div>
        <nav class="nav flex-column">
            <a class="nav-link text-danger" href="../logout.php">
                <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
            </a>
        </nav>
        
        <div class="text-center mt-4 mb-3">
            <small class="text-white-50">
                <i class="fas fa-circle" style="font-size: 8px; color: #2ecc71;"></i> System Online
            </small>
        </div>
    </div>
    
    <div class="main-content fade-in">
        <!-- ===== TOP HEADER WITH LOGO ===== -->
        <div class="top-header">
            <div class="header-left">
                <img src="../uploads/logo.jpg" alt="Logo" class="header-logo" onerror="this.src='https://cdn-icons-png.flaticon.com/512/2997/2997929.png'">
                <div>
                    <h4>Dashboard</h4>
                    <small><i class="fas fa-home"></i> Home / Dashboard</small>
                </div>
            </div>
            <div class="header-right">
                <div class="user-info">
                    <p><?php echo $_SESSION['full_name']; ?></p>
                    <small><i class="fas fa-user-shield"></i> Administrator</small>
                </div>
                <div class="user-avatar" data-bs-toggle="dropdown">
                    <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
                </div>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#"><i class="fas fa-user"></i> Profile</a></li>
                    <li><a class="dropdown-item" href="#"><i class="fas fa-cog"></i> Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Total Products</h6>
                                <h2 class="mb-0"><?php echo $stats['total_products']; ?></h2>
                            </div>
                            <i class="fas fa-boxes fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Low Stock Items</h6>
                                <h2 class="mb-0"><?php echo $stats['low_stock']; ?></h2>
                            </div>
                            <i class="fas fa-exclamation-triangle fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Total Value</h6>
                                <h2 class="mb-0">₱<?php echo number_format($stats['total_value'], 2); ?></h2>
                            </div>
                            <i class="fas fa-dollar-sign fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Today's Transactions</h6>
                                <h2 class="mb-0"><?php echo $stats['today_transactions']; ?></h2>
                            </div>
                            <i class="fas fa-exchange-alt fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Financial Mini Summary -->
        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <div class="financial-mini-card">
                    <div class="label"><i class="fas fa-arrow-down text-success"></i> TODAY'S INCOME</div>
                    <div class="amount text-success">₱<?php echo number_format($todayFinancial['today_income'] ?? 0, 2); ?></div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="financial-mini-card">
                    <div class="label"><i class="fas fa-arrow-up text-danger"></i> TODAY'S EXPENSE</div>
                    <div class="amount text-danger">₱<?php echo number_format($todayFinancial['today_expense'] ?? 0, 2); ?></div>
                </div>
            </div>
        </div>
        
        <!-- Bundle Production Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <div class="card" style="background: linear-gradient(135deg, #1e3c72, #2a5298); color: white;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1"><i class="fas fa-layer-group"></i> Total Batches Used</h6>
                                <h2 class="mb-0"><?php echo $totalBatchesUsed; ?> / 20</h2>
                            </div>
                            <i class="fas fa-cubes fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card" style="background: linear-gradient(135deg, #11998e, #38ef7d); color: white;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1"><i class="fas fa-industry"></i> Total Production</h6>
                                <h2 class="mb-0"><?php echo number_format($totalProduction); ?> pcs</h2>
                            </div>
                            <i class="fas fa-chart-line fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Low Stock Alert -->
        <?php if(count($lowStockProducts) > 0): ?>
        <div class="alert alert-warning alert-dismissible fade show mb-4">
            <h5><i class="fas fa-exclamation-triangle"></i> Low Stock Alert!</h5>
            <p>The following products are below their reorder level:</p>
            <ul>
                <?php foreach($lowStockProducts as $product): ?>
                <li><strong><?php echo $product['product_name']; ?></strong> - Current Stock: <?php echo $product['quantity']; ?> (Reorder at: <?php echo $product['reorder_level']; ?>)</li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <!-- Recent Transactions -->
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-history"></i> Recent Transactions</h5>
                <a href="transactions.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Product</th>
                                <th>Type</th>
                                <th>Quantity</th>
                                <th>User</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recentTransactions as $trans): ?>
                            <tr>
                                <td><?php echo date('M d, Y H:i', strtotime($trans['transaction_date'])); ?></td>
                                <td><?php echo $trans['product_name']; ?></td>
                                <td>
                                    <span class="badge <?php echo $trans['transaction_type'] == 'IN' ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo $trans['transaction_type']; ?>
                                    </span>
                                </td>
                                <td><?php echo $trans['quantity']; ?></td>
                                <td><?php echo $trans['full_name']; ?></td>
                                <td><?php echo $trans['remarks'] ?: '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(count($recentTransactions) == 0): ?>
                            <tr>
                                <td colspan="6" class="text-center">No transactions found</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="products.php" class="btn btn-outline-primary">
                                <i class="fas fa-plus"></i> Add Product
                            </a>
                            <a href="stock_in.php" class="btn btn-outline-success">
                                <i class="fas fa-arrow-down"></i> Stock In
                            </a>
                            <a href="stock_out.php" class="btn btn-outline-danger">
                                <i class="fas fa-arrow-up"></i> Stock Out
                            </a>
                            <a href="add_income.php" class="btn btn-outline-success">
                                <i class="fas fa-plus-circle"></i> Add Income
                            </a>
                            <a href="add_expense.php" class="btn btn-outline-danger">
                                <i class="fas fa-minus-circle"></i> Add Expense
                            </a>
                            <a href="bundle_reports.php" class="btn btn-outline-info">
                                <i class="fas fa-chart-pie"></i> Bundle Reports
                            </a>
                            <a href="financial_reports.php" class="btn btn-outline-warning">
                                <i class="fas fa-file-alt"></i> Financial Reports
                            </a>
                            <a href="users.php" class="btn btn-outline-secondary">
                                <i class="fas fa-user-plus"></i> Add Staff
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>