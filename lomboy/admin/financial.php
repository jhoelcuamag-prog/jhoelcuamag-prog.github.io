<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireAdmin();

// Handle Delete Transaction
if(isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    
    $stmt = $pdo->prepare("SELECT * FROM financial_transactions WHERE id = ?");
    $stmt->execute([$delete_id]);
    $transaction = $stmt->fetch();
    
    if($transaction) {
        if($transaction['transaction_type'] == 'expense' && $transaction['update_inventory'] == 1 && $transaction['product_id']) {
            $product = $pdo->prepare("SELECT quantity FROM products WHERE id = ?");
            $product->execute([$transaction['product_id']]);
            $current_qty = $product->fetch()['quantity'];
            $new_qty = $current_qty - $transaction['quantity'];
            $update = $pdo->prepare("UPDATE products SET quantity = ? WHERE id = ?");
            $update->execute([$new_qty, $transaction['product_id']]);
            $trans_log = $pdo->prepare("INSERT INTO transactions (product_id, user_id, transaction_type, quantity, previous_quantity, new_quantity, remarks) VALUES (?, ?, 'OUT', ?, ?, ?, ?)");
            $trans_log->execute([$transaction['product_id'], $_SESSION['user_id'], $transaction['quantity'], $current_qty, $new_qty, "DELETED EXPENSE: " . $transaction['description']]);
        }
        $delete = $pdo->prepare("DELETE FROM financial_transactions WHERE id = ?");
        if($delete->execute([$delete_id])) {
            $_SESSION['success_msg'] = "Transaction deleted successfully!";
            if($transaction['update_inventory'] == 1) {
                $_SESSION['success_msg'] .= " Inventory has been adjusted.";
            }
        } else {
            $_SESSION['error_msg'] = "Failed to delete transaction";
        }
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

if(isset($_SESSION['success_msg'])) {
    $success = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}
if(isset($_SESSION['error_msg'])) {
    $error = $_SESSION['error_msg'];
    unset($_SESSION['error_msg']);
}

$today = date('Y-m-d');
$thisMonth = date('Y-m-01');
$thisYear = date('Y-01-01');

$stmt = $pdo->prepare("SELECT SUM(CASE WHEN transaction_type = 'income' THEN total_amount ELSE 0 END) as today_income, SUM(CASE WHEN transaction_type = 'expense' THEN total_amount ELSE 0 END) as today_expense FROM financial_transactions WHERE date = ?");
$stmt->execute([$today]);
$todayStats = $stmt->fetch();

$stmt = $pdo->prepare("SELECT SUM(CASE WHEN transaction_type = 'income' THEN total_amount ELSE 0 END) as month_income, SUM(CASE WHEN transaction_type = 'expense' THEN total_amount ELSE 0 END) as month_expense FROM financial_transactions WHERE date >= ? AND date <= ?");
$stmt->execute([$thisMonth, $today]);
$monthStats = $stmt->fetch();

$stmt = $pdo->prepare("SELECT SUM(CASE WHEN transaction_type = 'income' THEN total_amount ELSE 0 END) as year_income, SUM(CASE WHEN transaction_type = 'expense' THEN total_amount ELSE 0 END) as year_expense FROM financial_transactions WHERE date >= ?");
$stmt->execute([$thisYear]);
$yearStats = $stmt->fetch();

$allTransactions = $pdo->query("
    SELECT ft.*, u.full_name as created_by_name, CASE WHEN ft.product_id IS NOT NULL THEN p.product_name ELSE NULL END as product_name
    FROM financial_transactions ft
    LEFT JOIN users u ON ft.created_by = u.id
    LEFT JOIN products p ON ft.product_id = p.id
    ORDER BY ft.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Management - Lombriks Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
        .card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); margin-bottom: 20px; transition: transform 0.3s; }
        .card:hover { transform: translateY(-3px); }
        .stat-card { background: white; border-radius: 15px; padding: 20px; text-align: center; }
        .stat-card.income { border-left: 4px solid #27ae60; }
        .stat-card.expense { border-left: 4px solid #e74c3c; }
        .stat-card.net { border-left: 4px solid #3498db; }
        .stat-value { font-size: 28px; font-weight: 700; }
        .stat-label { font-size: 13px; color: #7f8c8d; text-transform: uppercase; letter-spacing: 1px; }
        .btn-primary { background: linear-gradient(90deg, #667eea, #764ba2); border: none; border-radius: 20px; padding: 8px 20px; }
        .btn-success { background: #27ae60; border: none; border-radius: 20px; }
        .btn-danger { background: #e74c3c; border: none; border-radius: 20px; }
        .btn-sm-delete { background: #e74c3c; color: white; border: none; border-radius: 8px; padding: 5px 12px; font-size: 12px; transition: all 0.3s; cursor: pointer; }
        .btn-sm-delete:hover { background: #c0392b; transform: scale(1.05); }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in { animation: fadeIn 0.5s ease-out; }
        .quick-actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .transaction-table td { vertical-align: middle; }
        .badge-updated { background: #3498db; }
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
            <a class="nav-link" href="stock_in.php"><i class="fas fa-arrow-down"></i> <span>Stock In</span></a>
            <a class="nav-link" href="stock_out.php"><i class="fas fa-arrow-up"></i> <span>Stock Out</span></a>
        </nav>
        <div class="sidebar-title">FINANCIAL</div>
        <nav class="nav flex-column">
            <a class="nav-link active" href="financial.php"><i class="fas fa-chart-line"></i> <span>Financial Management</span></a>
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
            <h2><i class="fas fa-chart-line text-primary"></i> Financial Management</h2>
            <div class="quick-actions">
                <a href="add_income.php" class="btn btn-success"><i class="fas fa-plus-circle"></i> Add Income</a>
                <a href="add_expense.php" class="btn btn-danger"><i class="fas fa-minus-circle"></i> Add Expense</a>
                <a href="financial_reports.php" class="btn btn-primary"><i class="fas fa-file-alt"></i> Reports</a>
            </div>
        </div>
        
        <?php if(isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert"><i class="fas fa-check-circle"></i> <?php echo $success; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        <?php if(isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="stat-card income">
                    <div class="d-flex justify-content-between align-items-center">
                        <div><div class="stat-label">TODAY'S INCOME</div><div class="stat-value text-success">₱<?php echo number_format($todayStats['today_income'] ?? 0, 2); ?></div></div>
                        <i class="fas fa-arrow-down fa-3x text-success opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stat-card expense">
                    <div class="d-flex justify-content-between align-items-center">
                        <div><div class="stat-label">TODAY'S EXPENSE</div><div class="stat-value text-danger">₱<?php echo number_format($todayStats['today_expense'] ?? 0, 2); ?></div></div>
                        <i class="fas fa-arrow-up fa-3x text-danger opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stat-card net">
                    <div class="d-flex justify-content-between align-items-center">
                        <div><div class="stat-label">TODAY'S NET</div><div class="stat-value text-primary">₱<?php echo number_format(($todayStats['today_income'] ?? 0) - ($todayStats['today_expense'] ?? 0), 2); ?></div></div>
                        <i class="fas fa-chart-line fa-3x text-primary opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <div class="card"><div class="card-body"><h6 class="mb-3"><i class="fas fa-calendar-alt"></i> This Month Summary</h6>
                    <div class="row text-center">
                        <div class="col-4"><small class="text-muted">Income</small><h5 class="text-success">₱<?php echo number_format($monthStats['month_income'] ?? 0, 2); ?></h5></div>
                        <div class="col-4"><small class="text-muted">Expense</small><h5 class="text-danger">₱<?php echo number_format($monthStats['month_expense'] ?? 0, 2); ?></h5></div>
                        <div class="col-4"><small class="text-muted">Net</small><h5 class="text-primary">₱<?php echo number_format(($monthStats['month_income'] ?? 0) - ($monthStats['month_expense'] ?? 0), 2); ?></h5></div>
                    </div>
                </div></div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card"><div class="card-body"><h6 class="mb-3"><i class="fas fa-chart-line"></i> This Year Summary</h6>
                    <div class="row text-center">
                        <div class="col-4"><small class="text-muted">Income</small><h5 class="text-success">₱<?php echo number_format($yearStats['year_income'] ?? 0, 2); ?></h5></div>
                        <div class="col-4"><small class="text-muted">Expense</small><h5 class="text-danger">₱<?php echo number_format($yearStats['year_expense'] ?? 0, 2); ?></h5></div>
                        <div class="col-4"><small class="text-muted">Net</small><h5 class="text-primary">₱<?php echo number_format(($yearStats['year_income'] ?? 0) - ($yearStats['year_expense'] ?? 0), 2); ?></h5></div>
                    </div>
                </div></div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-list"></i> All Financial Transactions</h5>
                <span class="badge bg-primary">Total: <?php echo count($allTransactions); ?> records</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover transaction-table">
                        <thead><tr><th>ID</th><th>Transaction ID</th><th>Date</th><th>Type</th><th>Description</th><th>Item/Product</th><th>Qty</th><th>Amount</th><th>Inventory Updated</th><th>Created By</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php foreach($allTransactions as $trans): ?>
                            <tr>
                                <td><?php echo $trans['id']; ?></td>
                                <td><code><?php echo htmlspecialchars($trans['transaction_id']); ?></code></td>
                                <td><?php echo date('M d, Y', strtotime($trans['date'])); ?></td>
                                <td><span class="badge <?php echo $trans['transaction_type'] == 'income' ? 'bg-success' : 'bg-danger'; ?>"><i class="fas <?php echo $trans['transaction_type'] == 'income' ? 'fa-arrow-down' : 'fa-arrow-up'; ?>"></i> <?php echo ucfirst($trans['transaction_type']); ?></span></td>
                                <td><?php echo htmlspecialchars(substr($trans['description'], 0, 40)); ?>...</td>
                                <td><?php if($trans['product_name']): ?><span class="badge bg-info"><?php echo htmlspecialchars($trans['product_name']); ?></span><?php else: ?><?php echo htmlspecialchars($trans['item_name'] ?: '-'); ?><?php endif; ?></td>
                                <td><?php echo $trans['quantity'] ?: '-'; ?></td>
                                <td><strong>₱<?php echo number_format($trans['total_amount'], 2); ?></strong></td>
                                <td><?php if($trans['update_inventory'] == 1): ?><span class="badge badge-updated"><i class="fas fa-check-circle"></i> Yes</span><?php else: ?><span class="badge bg-secondary">No</span><?php endif; ?></td>
                                <td><?php echo htmlspecialchars($trans['created_by_name']); ?></td>
                                <td>
                                    <form method="POST" class="delete-form" onsubmit="return confirm('Delete this transaction? This cannot be undone!');">
                                        <input type="hidden" name="delete_id" value="<?php echo $trans['id']; ?>">
                                        <button type="submit" class="btn-sm-delete"><i class="fas fa-trash"></i> Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(count($allTransactions) == 0): ?>
                            <tr><td colspan="11" class="text-center py-4"><i class="fas fa-coins fa-3x text-muted mb-2"></i><h6>No financial transactions yet</h6><p class="text-muted">Click "Add Income" or "Add Expense" to get started.</p></td></tr>
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