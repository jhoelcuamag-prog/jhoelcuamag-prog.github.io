<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

if($_SESSION['role'] != 'staff') {
    header("Location: ../admin/dashboard.php");
    exit();
}

$today = date('Y-m-d');

$stmt = $pdo->prepare("
    SELECT 
        SUM(CASE WHEN transaction_type = 'income' THEN total_amount ELSE 0 END) as today_income,
        SUM(CASE WHEN transaction_type = 'expense' THEN total_amount ELSE 0 END) as today_expense
    FROM financial_transactions WHERE date = ?
");
$stmt->execute([$today]);
$todayStats = $stmt->fetch();

$recentTransactions = $pdo->query("
    SELECT ft.*, u.full_name as created_by_name 
    FROM financial_transactions ft
    LEFT JOIN users u ON ft.created_by = u.id
    ORDER BY ft.created_at DESC LIMIT 10
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial - Lombriks Staff</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f0f2f5; }
        
        .sidebar { width: 260px; background: linear-gradient(180deg, #1a252f 0%, #2c3e50 100%); min-height: 100vh; height: 100vh; position: fixed; left: 0; top: 0; z-index: 100; box-shadow: 2px 0 15px rgba(0,0,0,0.1); overflow-y: auto; overflow-x: hidden; }
        .sidebar::-webkit-scrollbar { width: 5px; }
        .sidebar::-webkit-scrollbar-track { background: rgba(255,255,255,0.05); border-radius: 10px; }
        .sidebar::-webkit-scrollbar-thumb { background: linear-gradient(180deg, #667eea, #764ba2); border-radius: 10px; }
        .sidebar::-webkit-scrollbar-thumb:hover { background: linear-gradient(180deg, #764ba2, #667eea); }
        .sidebar { scrollbar-width: thin; scrollbar-color: #667eea rgba(255,255,255,0.05); }
        
        .sidebar .sidebar-logo { text-align: center; padding: 20px 0 15px; border-bottom: 1px solid rgba(255,255,255,0.05); position: sticky; top: 0; background: linear-gradient(180deg, #1a252f 0%, #2c3e50 100%); z-index: 10; }
        .sidebar .sidebar-logo img { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid rgba(255,255,255,0.2); transition: all 0.3s; }
        .sidebar .sidebar-logo img:hover { transform: scale(1.08); border-color: #667eea; box-shadow: 0 0 25px rgba(102,126,234,0.3); }
        .sidebar .sidebar-logo h5 { color: white; margin-top: 10px; font-weight: 700; letter-spacing: 2px; font-size: 18px; }
        .sidebar .sidebar-logo p { color: rgba(255,255,255,0.5); font-size: 12px; margin-bottom: 0; }
        .sidebar .nav-link { color: rgba(255,255,255,0.7); padding: 12px 20px; margin: 3px 8px; border-radius: 10px; transition: all 0.3s; font-size: 14px; }
        .sidebar .nav-link:hover { background: rgba(255,255,255,0.08); transform: translateX(5px); color: white; }
        .sidebar .nav-link.active { background: linear-gradient(90deg, #667eea, #764ba2); color: white; box-shadow: 0 5px 15px rgba(102,126,234,0.3); }
        .sidebar .nav-link i { margin-right: 12px; width: 20px; font-size: 16px; }
        .sidebar-title { font-size: 11px; color: rgba(255,255,255,0.3); margin: 20px 20px 10px; text-transform: uppercase; letter-spacing: 2px; font-weight: 600; }
        .sidebar-bottom { padding: 15px 20px; border-top: 1px solid rgba(255,255,255,0.05); text-align: center; position: sticky; bottom: 0; background: linear-gradient(180deg, #2c3e50 0%, #1a252f 100%); z-index: 10; }
        .sidebar-bottom small { color: rgba(255,255,255,0.4); font-size: 11px; }
        .sidebar-bottom .online-status { display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #2ecc71; animation: pulse-dot 2s infinite; }
        @keyframes pulse-dot { 0% { opacity: 1; transform: scale(1); } 50% { opacity: 0.5; transform: scale(0.8); } 100% { opacity: 1; transform: scale(1); } }
        
        .main-content { margin-left: 260px; padding: 20px; }
        .card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); margin-bottom: 20px; }
        .stat-card { background: white; border-radius: 15px; padding: 20px; text-align: center; border-left: 4px solid; }
        .stat-card.income { border-left-color: #27ae60; }
        .stat-card.expense { border-left-color: #e74c3c; }
        .stat-value { font-size: 24px; font-weight: 700; }
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
            <a class="nav-link" href="products.php"><i class="fas fa-boxes"></i> <span>Products</span></a>
            <a class="nav-link" href="bundles.php"><i class="fas fa-gift"></i> <span>Bundle List</span></a>
            <a class="nav-link" href="stock_in.php"><i class="fas fa-arrow-down"></i> <span>Stock In</span></a>
            <a class="nav-link" href="stock_out.php"><i class="fas fa-arrow-up"></i> <span>Stock Out</span></a>
        </nav>
        
        <div class="sidebar-title">FINANCIAL</div>
        <nav class="nav flex-column">
            <a class="nav-link active" href="financial.php"><i class="fas fa-chart-line"></i> <span>Financial Overview</span></a>
            <a class="nav-link" href="financial_reports.php"><i class="fas fa-file-alt"></i> <span>View Reports</span></a>
        </nav>
        
        <div class="sidebar-title">REPORTS</div>
        <nav class="nav flex-column">
            <a class="nav-link" href="transactions.php"><i class="fas fa-history"></i> <span>My Transactions</span></a>
            <a class="nav-link" href="reports.php"><i class="fas fa-chart-bar"></i> <span>Reports</span></a>
            <a class="nav-link" href="low_stock.php"><i class="fas fa-exclamation-triangle"></i> <span>Low Stock</span></a>
        </nav>
        
        <div class="sidebar-title">ACCOUNT</div>
        <nav class="nav flex-column">
            <a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </nav>
        
        <div class="sidebar-bottom">
            <small><span class="online-status"></span> System Online</small>
        </div>
    </div>
    
    <div class="main-content fade-in">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-chart-line text-primary"></i> Financial Overview</h2>
            <div class="alert alert-info mb-0">
                <i class="fas fa-info-circle"></i> View Only - Staff cannot add/edit financial transactions
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <div class="stat-card income">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">TODAY'S INCOME</div>
                            <div class="stat-value text-success">₱<?php echo number_format($todayStats['today_income'] ?? 0, 2); ?></div>
                        </div>
                        <i class="fas fa-arrow-down fa-3x text-success opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="stat-card expense">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">TODAY'S EXPENSE</div>
                            <div class="stat-value text-danger">₱<?php echo number_format($todayStats['today_expense'] ?? 0, 2); ?></div>
                        </div>
                        <i class="fas fa-arrow-up fa-3x text-danger opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-history"></i> Recent Transactions</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Transaction ID</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Created By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recentTransactions as $trans): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($trans['date'])); ?></td>
                                <td><code><?php echo htmlspecialchars($trans['transaction_id']); ?></code></td>
                                <td>
                                    <span class="badge <?php echo $trans['transaction_type'] == 'income' ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo ucfirst($trans['transaction_type']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars(substr($trans['description'], 0, 40)); ?>...</td>
                                <td><strong>₱<?php echo number_format($trans['total_amount'], 2); ?></strong></td>
                                <td><?php echo htmlspecialchars($trans['created_by_name']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(count($recentTransactions) == 0): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="fas fa-coins fa-3x text-muted mb-3"></i>
                                    <h6>No transactions found</h6>
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