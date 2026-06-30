<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

if($_SESSION['role'] != 'staff') {
    header("Location: ../admin/dashboard.php");
    exit();
}

$filter_type = $_GET['type'] ?? '';
$filter_date = $_GET['date'] ?? '';

$query = "SELECT t.*, p.product_name, p.product_code 
          FROM transactions t 
          JOIN products p ON t.product_id = p.id 
          WHERE t.user_id = ?";
$params = [$_SESSION['user_id']];

if($filter_type) {
    $query .= " AND t.transaction_type = ?";
    $params[] = $filter_type;
}
if($filter_date) {
    $query .= " AND DATE(t.transaction_date) = ?";
    $params[] = $filter_date;
}

$query .= " ORDER BY t.transaction_date DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$transactions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Transactions - Lombriks (Staff)</title>
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
        .card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
        .btn-primary { background: linear-gradient(90deg, #667eea, #764ba2); border: none; border-radius: 20px; padding: 8px 20px; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102,126,234,0.4); }
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
            <a class="nav-link" href="financial.php"><i class="fas fa-chart-line"></i> <span>Financial Overview</span></a>
            <a class="nav-link" href="financial_reports.php"><i class="fas fa-file-alt"></i> <span>View Reports</span></a>
        </nav>
        
        <div class="sidebar-title">REPORTS</div>
        <nav class="nav flex-column">
            <a class="nav-link active" href="transactions.php"><i class="fas fa-history"></i> <span>My Transactions</span></a>
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
            <h2><i class="fas fa-history text-primary"></i> My Transaction History</h2>
            <div><span class="badge bg-primary">Total: <?php echo count($transactions); ?> records</span></div>
        </div>
        
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label>Transaction Type</label>
                        <select name="type" class="form-select">
                            <option value="">All</option>
                            <option value="IN" <?php echo $filter_type == 'IN' ? 'selected' : ''; ?>>Stock In</option>
                            <option value="OUT" <?php echo $filter_type == 'OUT' ? 'selected' : ''; ?>>Stock Out</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Date</label>
                        <input type="date" name="date" class="form-control" value="<?php echo $filter_date; ?>">
                    </div>
                    <div class="col-md-4">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary d-block w-100"><i class="fas fa-filter"></i> Filter</button>
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
                                <th>Date & Time</th>
                                <th>Product</th>
                                <th>Type</th>
                                <th>Quantity</th>
                                <th>Previous Qty</th>
                                <th>New Qty</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($transactions as $trans): ?>
                            <tr>
                                <td><?php echo date('M d, Y H:i:s', strtotime($trans['transaction_date'])); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($trans['product_code']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($trans['product_name']); ?></small>
                                </td>
                                <td>
                                    <span class="badge <?php echo $trans['transaction_type'] == 'IN' ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo $trans['transaction_type']; ?>
                                    </span>
                                </td>
                                <td><?php echo $trans['quantity']; ?></td>
                                <td><?php echo $trans['previous_quantity']; ?></td>
                                <td><?php echo $trans['new_quantity']; ?></td>
                                <td><?php echo htmlspecialchars($trans['remarks'] ?: '-'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(count($transactions) == 0): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <h6>No transactions found</h6>
                                    <p class="text-muted">Try adjusting your filter criteria.</p>
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