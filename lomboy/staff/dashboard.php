<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

if($_SESSION['role'] != 'staff') {
    header("Location: ../admin/dashboard.php");
    exit();
}

$stats = getDashboardStats($pdo);
$lowStockProducts = getLowStockProducts($pdo);
$recentTransactions = $pdo->prepare("
    SELECT t.*, p.product_name, u.full_name 
    FROM transactions t 
    JOIN products p ON t.product_id = p.id 
    JOIN users u ON t.user_id = u.id 
    WHERE t.user_id = ? 
    ORDER BY t.transaction_date DESC LIMIT 10
");
$recentTransactions->execute([$_SESSION['user_id']]);
$transactions = $recentTransactions->fetchAll();

// Get financial summary for staff (view only)
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
    <title>Staff Dashboard - Lombriks</title>
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
        .top-header .header-left .header-logo:hover {
            transform: scale(1.05);
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
        .card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
        .stat-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .financial-mini-card { 
            background: white; 
            border-radius: 12px; 
            padding: 12px; 
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in { animation: fadeIn 0.5s ease-out; }
        
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar .sidebar-logo h5, .sidebar .sidebar-logo p, .sidebar-title { display: none; }
            .sidebar .nav-link span { display: none; }
            .sidebar .nav-link i { margin-right: 0; font-size: 20px; }
            .main-content { margin-left: 70px; }
            .sidebar .sidebar-logo img { width: 40px; height: 40px; }
            .sidebar .sidebar-logo { padding: 10px 0; }
            .top-header { flex-direction: column; gap: 10px; text-align: center; }
        }
    </style>
</head>
<body>
    <!-- ===== SIDEBAR WITH LOGO AND SCROLL BAR ===== -->
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
            <a class="nav-link" href="bundles.php">
                <i class="fas fa-gift"></i> <span>Bundle List</span>
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
                <i class="fas fa-chart-line"></i> <span>Financial Overview</span>
            </a>
            <a class="nav-link" href="financial_reports.php">
                <i class="fas fa-file-alt"></i> <span>View Reports</span>
            </a>
        </nav>
        
        <div class="sidebar-title">REPORTS</div>
        <nav class="nav flex-column">
            <a class="nav-link" href="transactions.php">
                <i class="fas fa-history"></i> <span>My Transactions</span>
            </a>
            <a class="nav-link" href="reports.php">
                <i class="fas fa-chart-bar"></i> <span>Reports</span>
            </a>
            <a class="nav-link" href="low_stock.php">
                <i class="fas fa-exclamation-triangle"></i> <span>Low Stock</span>
            </a>
        </nav>
        
        <div class="sidebar-title">ACCOUNT</div>
        <nav class="nav flex-column">
            <a class="nav-link text-danger" href="../logout.php">
                <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
            </a>
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
        <!-- ===== TOP HEADER WITH LOGO ===== -->
        <div class="top-header">
            <div class="header-left">
                <img src="../uploads/logo.jpg" alt="Lombriks Logo" class="header-logo" onerror="this.src='https://cdn-icons-png.flaticon.com/512/2997/2997929.png'">
                <div>
                    <h4>Dashboard</h4>
                    <small><i class="fas fa-home"></i> Home / Dashboard</small>
                </div>
            </div>
            <div class="header-right">
                <div class="user-info">
                    <p><?php echo $_SESSION['full_name']; ?></p>
                    <small><i class="fas fa-user-tie"></i> Staff Member</small>
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
            <div class="col-md-4 mb-3">
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
            <div class="col-md-4 mb-3">
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
            <div class="col-md-4 mb-3">
                <div class="card stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Your Transactions</h6>
                                <h2 class="mb-0"><?php echo count($transactions); ?></h2>
                            </div>
                            <i class="fas fa-exchange-alt fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Financial Mini Summary for Staff -->
        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <div class="financial-mini-card">
                    <div class="text-muted small"><i class="fas fa-arrow-down text-success"></i> TODAY'S INCOME</div>
                    <div class="text-success fs-4 fw-bold">₱<?php echo number_format($todayFinancial['today_income'] ?? 0, 2); ?></div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="financial-mini-card">
                    <div class="text-muted small"><i class="fas fa-arrow-up text-danger"></i> TODAY'S EXPENSE</div>
                    <div class="text-danger fs-4 fw-bold">₱<?php echo number_format($todayFinancial['today_expense'] ?? 0, 2); ?></div>
                </div>
            </div>
        </div>
        
        <?php if(count($lowStockProducts) > 0): ?>
        <div class="alert alert-warning mb-4">
            <h5><i class="fas fa-exclamation-triangle"></i> Low Stock Alert!</h5>
            <p><?php echo count($lowStockProducts); ?> products need restocking. Please notify the admin.</p>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-history"></i> Your Recent Transactions</h5>
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
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($transactions as $trans): ?>
                            <tr>
                                <td><?php echo date('M d, Y H:i', strtotime($trans['transaction_date'])); ?></td>
                                <td><?php echo htmlspecialchars($trans['product_name']); ?></td>
                                <td>
                                    <span class="badge <?php echo $trans['transaction_type'] == 'IN' ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo $trans['transaction_type']; ?>
                                    </span>
                                </td>
                                <td><?php echo $trans['quantity']; ?></td>
                                <td><?php echo htmlspecialchars($trans['remarks'] ?: '-'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(count($transactions) == 0): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <h6>No transactions found</h6>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions for Staff -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="stock_in.php" class="btn btn-outline-success">
                                <i class="fas fa-arrow-down"></i> Stock In
                            </a>
                            <a href="stock_out.php" class="btn btn-outline-danger">
                                <i class="fas fa-arrow-up"></i> Stock Out
                            </a>
                            <a href="bundles.php" class="btn btn-outline-info">
                                <i class="fas fa-gift"></i> Bundle List
                            </a>
                            <a href="financial_reports.php" class="btn btn-outline-primary">
                                <i class="fas fa-file-alt"></i> View Reports
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