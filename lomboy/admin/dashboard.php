<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireAdmin();

$stats = getDashboardStats($pdo);
$lowStockProducts = getLowStockProducts($pdo);
$recentTransactions = $pdo->query("SELECT t.*, p.product_name, u.full_name FROM transactions t JOIN products p ON t.product_id = p.id JOIN users u ON t.user_id = u.id ORDER BY t.transaction_date DESC LIMIT 10")->fetchAll();

$totalBatchesUsed = $pdo->query("SELECT COUNT(DISTINCT batch_no) as total FROM bundle_production")->fetch()['total'] ?? 0;
$totalProduction = $pdo->query("SELECT SUM(quantity) as total FROM bundle_production WHERE type = 'production'")->fetch()['total'] ?? 0;

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
        
        /* ===== SIDEBAR WITH SCROLL BAR ===== */
        .sidebar { 
            width: 260px; 
            background: linear-gradient(180deg, #1a252f 0%, #2c3e50 100%); 
            min-height: 100vh; 
            height: 100vh;
            position: fixed; 
            left: 0; 
            top: 0; 
            z-index: 100; 
            box-shadow: 2px 0 15px rgba(0,0,0,0.1);
            overflow-y: auto;
            overflow-x: hidden;
        }

        /* Custom Scrollbar for Sidebar */
        .sidebar::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.05);
            border-radius: 10px;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #667eea, #764ba2);
            border-radius: 10px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, #764ba2, #667eea);
        }

        /* Firefox Scrollbar */
        .sidebar {
            scrollbar-width: thin;
            scrollbar-color: #667eea rgba(255,255,255,0.05);
        }

        .sidebar .sidebar-logo {
            text-align: center;
            padding: 20px 0 15px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            position: sticky;
            top: 0;
            background: linear-gradient(180deg, #1a252f 0%, #2c3e50 100%);
            z-index: 10;
        }

        .sidebar .sidebar-logo img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255,255,255,0.2);
            transition: all 0.3s;
        }

        .sidebar .sidebar-logo img:hover {
            transform: scale(1.08);
            border-color: #667eea;
            box-shadow: 0 0 25px rgba(102,126,234,0.3);
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

        /* Sidebar Bottom Status */
        .sidebar-bottom {
            padding: 15px 20px;
            border-top: 1px solid rgba(255,255,255,0.05);
            text-align: center;
            position: sticky;
            bottom: 0;
            background: linear-gradient(180deg, #2c3e50 0%, #1a252f 100%);
            z-index: 10;
        }

        .sidebar-bottom small {
            color: rgba(255,255,255,0.4);
            font-size: 11px;
        }

        .sidebar-bottom .online-status {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #2ecc71;
            animation: pulse-dot 2s infinite;
        }

        @keyframes pulse-dot {
            0% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.8); }
            100% { opacity: 1; transform: scale(1); }
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
        .financial-mini-card { background: white; border-radius: 12px; padding: 12px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .financial-mini-card .amount { font-size: 18px; font-weight: 700; }
        .financial-mini-card .label { font-size: 11px; color: #7f8c8d; text-transform: uppercase; }
        
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar .sidebar-logo h5, .sidebar .sidebar-logo p, .sidebar-title { display: none; }
            .sidebar .nav-link span { display: none; }
            .sidebar .nav-link i { margin-right: 0; font-size: 20px; }
            .main-content { margin-left: 70px; }
            .sidebar .sidebar-logo img { width: 40px; height: 40px; }
            .sidebar .sidebar-logo { padding: 10px 0; }
        }
    </style>
</head>
<body>
    <!-- ===== SIDEBAR WITH SCROLL BAR ===== -->
    <div class="sidebar">
        <div class="sidebar-logo">
            <img src="../uploads/logo.jpg" alt="Lombriks Logo" onerror="this.src='https://cdn-icons-png.flaticon.com/512/2997/2997929.png'">
            <h5>LOMBRIKS</h5>
            <p>Inventory System</p>
        </div>
        
        <div class="sidebar-title">MAIN NAVIGATION</div>
        <nav class="nav flex-column">
            <a class="nav-link active" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
            <a class="nav-link" href="products.php"><i class="fas fa-boxes"></i> <span>Products</span></a>
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
        
        <div class="sidebar-bottom">
            <small>
                <span class="online-status"></span> System Online
                <br>
                <span style="font-size: 10px; opacity: 0.6;">v2.0</span>
            </small>
        </div>
    </div>
    
    <div class="main-content fade-in">
        <!-- Main content here -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-tachometer-alt text-primary"></i> Dashboard</h2>
            <div class="text-end">
                <p class="mb-0"><strong><?php echo $_SESSION['full_name']; ?></strong></p>
                <small class="text-muted">Administrator</small>
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
        
        <!-- Bundle Production Summary -->
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
                            <a href="products.php" class="btn btn-outline-primary"><i class="fas fa-plus"></i> Add Product</a>
                            <a href="stock_in.php" class="btn btn-outline-success"><i class="fas fa-arrow-down"></i> Stock In</a>
                            <a href="stock_out.php" class="btn btn-outline-danger"><i class="fas fa-arrow-up"></i> Stock Out</a>
                            <a href="add_income.php" class="btn btn-outline-success"><i class="fas fa-plus-circle"></i> Add Income</a>
                            <a href="add_expense.php" class="btn btn-outline-danger"><i class="fas fa-minus-circle"></i> Add Expense</a>
                            <a href="bundle_reports.php" class="btn btn-outline-info"><i class="fas fa-chart-pie"></i> Bundle Reports</a>
                            <a href="financial_reports.php" class="btn btn-outline-warning"><i class="fas fa-file-alt"></i> Financial Reports</a>
                            <a href="users.php" class="btn btn-outline-secondary"><i class="fas fa-user-plus"></i> Add Staff</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>