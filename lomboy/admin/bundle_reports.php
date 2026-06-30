<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireAdmin();

// Get all production data from bundle_production table
$allProduction = $pdo->query("
    SELECT bp.*, u.full_name as created_by_name 
    FROM bundle_production bp
    LEFT JOIN users u ON bp.created_by = u.id
    ORDER BY bp.batch_no ASC, bp.created_at DESC
")->fetchAll();

// Group by batch
$batchData = [];
foreach($allProduction as $prod) {
    $batchData[$prod['batch_no']][] = $prod;
}

// Calculate totals
$totalV1 = $pdo->query("SELECT SUM(quantity) as total FROM bundle_production WHERE version = 'Version 1' AND type = 'production'")->fetch()['total'] ?? 0;
$totalV2 = $pdo->query("SELECT SUM(quantity) as total FROM bundle_production WHERE version = 'Version 2' AND type = 'production'")->fetch()['total'] ?? 0;
$totalReject = $pdo->query("SELECT SUM(quantity) as total FROM bundle_production WHERE type = 'reject'")->fetch()['total'] ?? 0;
$totalBatches = $pdo->query("SELECT COUNT(DISTINCT batch_no) as total FROM bundle_production")->fetch()['total'] ?? 0;
$totalRecords = $pdo->query("SELECT COUNT(*) as total FROM bundle_production")->fetch()['total'] ?? 0;

// Per batch totals
$batchTotals = [];
for($i = 1; $i <= 20; $i++) {
    $v1 = $pdo->prepare("SELECT SUM(quantity) as total FROM bundle_production WHERE batch_no = ? AND version = 'Version 1' AND type = 'production'");
    $v1->execute([$i]);
    $batchTotals[$i]['v1'] = $v1->fetch()['total'] ?? 0;
    
    $v2 = $pdo->prepare("SELECT SUM(quantity) as total FROM bundle_production WHERE batch_no = ? AND version = 'Version 2' AND type = 'production'");
    $v2->execute([$i]);
    $batchTotals[$i]['v2'] = $v2->fetch()['total'] ?? 0;
    
    $reject = $pdo->prepare("SELECT SUM(quantity) as total FROM bundle_production WHERE batch_no = ? AND type = 'reject'");
    $reject->execute([$i]);
    $batchTotals[$i]['reject'] = $reject->fetch()['total'] ?? 0;
    
    $batchTotals[$i]['total'] = $batchTotals[$i]['v1'] + $batchTotals[$i]['v2'];
}

// Save report to bundle_reports table
if(isset($_POST['save_report'])) {
    $report_date = date('Y-m-d');
    $stmt = $pdo->prepare("INSERT INTO bundle_reports (report_date, total_batches, total_v1, total_v2, total_reject, total_records, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$report_date, $totalBatches, $totalV1, $totalV2, $totalReject, $totalRecords, $_SESSION['user_id']]);
    $success = "Report saved successfully!";
}

// Export to CSV
if(isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="bundle_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Batch No', 'Version 1 (pcs)', 'Version 2 (pcs)', 'Reject (pcs)', 'Total Production (pcs)', 'Status']);
    
    for($i = 1; $i <= 20; $i++) {
        $status = ($batchTotals[$i]['total'] > 0) ? 'Active' : 'No Production';
        fputcsv($output, [$i, $batchTotals[$i]['v1'], $batchTotals[$i]['v2'], $batchTotals[$i]['reject'], $batchTotals[$i]['total'], $status]);
    }
    
    fputcsv($output, ['GRAND TOTAL', $totalV1, $totalV2, $totalReject, $totalV1 + $totalV2, '']);
    fclose($output);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bundle Reports - Lombriks Admin</title>
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
        .card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); margin-bottom: 20px; }
        .btn-primary { background: linear-gradient(90deg, #667eea, #764ba2); border: none; border-radius: 20px; padding: 8px 20px; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102,126,234,0.4); }
        .btn-success { background: #27ae60; border: none; border-radius: 20px; padding: 8px 20px; }
        .btn-info { background: #3498db; border: none; border-radius: 20px; padding: 8px 20px; color: white; }
        .stats-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 15px; padding: 20px; margin-bottom: 20px; }
        .stats-card h3 { font-size: 32px; font-weight: bold; margin: 0; }
        .table th { background: #f8f9fa; font-weight: 600; }
        .batch-card { border-left: 4px solid; margin-bottom: 10px; transition: transform 0.2s; }
        .batch-card:hover { transform: translateX(5px); }
        @media print {
            .sidebar, .no-print, .btn, .card-header .btn { display: none; }
            .main-content { margin-left: 0; padding: 0; }
            .card { box-shadow: none; }
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in { animation: fadeIn 0.5s ease-out; }
        
        /* Improved Table Styles */
        .report-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .report-table thead th {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            font-weight: 600;
            padding: 15px;
            font-size: 14px;
            text-align: center;
            border: none;
        }
        .report-table tbody td {
            padding: 12px 15px;
            text-align: center;
            vertical-align: middle;
            border-bottom: 1px solid #e9ecef;
        }
        .report-table tbody tr:hover {
            background: #f8f9fa;
        }
        .report-table tfoot td {
            background: #2c3e50;
            color: white;
            font-weight: 700;
            padding: 15px;
            text-align: center;
        }
        .batch-number {
            font-weight: 700;
            font-size: 16px;
            color: #2c3e50;
        }
        .quantity-cell {
            font-weight: 600;
            font-size: 15px;
        }
        .quantity-v1 { color: #667eea; }
        .quantity-v2 { color: #764ba2; }
        .quantity-reject { color: #e74c3c; }
        .quantity-total { color: #27ae60; font-weight: 700; font-size: 16px; }
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8d7da; color: #721c24; }
        .btn-details {
            background: linear-gradient(90deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 20px;
            padding: 5px 15px;
            font-size: 12px;
            transition: all 0.3s;
        }
        .btn-details:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 8px rgba(102,126,234,0.4);
        }
        .badge-count {
            font-size: 20px;
            font-weight: 700;
            display: block;
        }
        .summary-box {
            text-align: center;
            padding: 15px;
            border-radius: 12px;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .summary-box .icon {
            font-size: 30px;
            margin-bottom: 10px;
        }
        .summary-box .label {
            font-size: 13px;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .summary-box .value {
            font-size: 28px;
            font-weight: 700;
            color: #2c3e50;
        }
        
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
            <a class="nav-link active" href="bundle_reports.php"><i class="fas fa-chart-pie"></i> <span>Bundle Reports</span></a>
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
            <h2><i class="fas fa-chart-pie text-primary"></i> Bundle Production Reports</h2>
            <div class="no-print">
                <a href="?export=1" class="btn btn-info"><i class="fas fa-file-excel"></i> Export CSV</a>
                <button onclick="window.print()" class="btn btn-success"><i class="fas fa-print"></i> Print Report</button>
            </div>
        </div>
        
        <?php if(isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Summary Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="summary-box">
                    <div class="icon"><i class="fas fa-layer-group" style="color: #667eea;"></i></div>
                    <div class="label">Total Batches</div>
                    <div class="value"><?php echo $totalBatches; ?> <small style="font-size: 14px;">/ 20</small></div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="summary-box">
                    <div class="icon"><i class="fas fa-code-branch" style="color: #27ae60;"></i></div>
                    <div class="label">Version 1 Total</div>
                    <div class="value"><?php echo number_format($totalV1); ?> <small>pcs</small></div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="summary-box">
                    <div class="icon"><i class="fas fa-code-branch" style="color: #764ba2;"></i></div>
                    <div class="label">Version 2 Total</div>
                    <div class="value"><?php echo number_format($totalV2); ?> <small>pcs</small></div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="summary-box">
                    <div class="icon"><i class="fas fa-times-circle" style="color: #e74c3c;"></i></div>
                    <div class="label">Reject Total</div>
                    <div class="value"><?php echo number_format($totalReject); ?> <small>pcs</small></div>
                </div>
            </div>
        </div>
        
        <!-- Grand Total Card -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row text-center align-items-center">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <div class="alert alert-primary mb-0" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none;">
                            <h6 class="mb-1"><i class="fas fa-chart-line"></i> TOTAL PRODUCTION</h6>
                            <h2 class="mb-0"><?php echo number_format($totalV1 + $totalV2); ?> <small>pcs</small></h2>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3 mb-md-0">
                        <div class="alert alert-info mb-0" style="background: #3498db; color: white; border: none;">
                            <h6 class="mb-1"><i class="fas fa-database"></i> TOTAL RECORDS</h6>
                            <h2 class="mb-0"><?php echo number_format($totalRecords); ?></h2>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <form method="POST" class="d-inline">
                            <button type="submit" name="save_report" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Current Report
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Batch by Batch Detailed Report -->
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-table"></i> Batch Production Report (Batch 01 - 20)</h5>
                <span class="badge bg-primary"><?php echo $totalBatches; ?> Batches with Production</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th style="width: 10%;">BATCH NO.</th>
                                <th style="width: 18%;">VERSION 1 (pcs)</th>
                                <th style="width: 18%;">VERSION 2 (pcs)</th>
                                <th style="width: 18%;">REJECT (pcs)</th>
                                <th style="width: 18%;">TOTAL PRODUCTION (pcs)</th>
                                <th style="width: 10%;">STATUS</th>
                                <th style="width: 8%;">DETAILS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for($i = 1; $i <= 20; $i++): ?>
                            <?php 
                            $v1Total = $batchTotals[$i]['v1'];
                            $v2Total = $batchTotals[$i]['v2'];
                            $rejectTotal = $batchTotals[$i]['reject'];
                            $totalProd = $batchTotals[$i]['total'];
                            
                            $status = ($totalProd > 0) ? 'Active' : 'No Production';
                            $statusClass = ($totalProd > 0) ? 'status-active' : 'status-inactive';
                            $rowClass = ($totalProd > 0) ? '' : 'opacity-75';
                            ?>
                            <tr class="<?php echo $rowClass; ?>">
                                <td class="batch-number">
                                    <i class="fas fa-cube me-2" style="color: #667eea;"></i>
                                    Batch <?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>
                                </td>
                                <td class="quantity-cell quantity-v1">
                                    <?php if($v1Total > 0): ?>
                                        <i class="fas fa-check-circle text-success me-1"></i>
                                    <?php else: ?>
                                        <i class="fas fa-minus-circle text-muted me-1"></i>
                                    <?php endif; ?>
                                    <?php echo number_format($v1Total); ?>
                                </td>
                                <td class="quantity-cell quantity-v2">
                                    <?php if($v2Total > 0): ?>
                                        <i class="fas fa-check-circle text-success me-1"></i>
                                    <?php else: ?>
                                        <i class="fas fa-minus-circle text-muted me-1"></i>
                                    <?php endif; ?>
                                    <?php echo number_format($v2Total); ?>
                                </td>
                                <td class="quantity-cell quantity-reject">
                                    <?php if($rejectTotal > 0): ?>
                                        <i class="fas fa-exclamation-triangle text-warning me-1"></i>
                                    <?php else: ?>
                                        <i class="fas fa-check-circle text-success me-1"></i>
                                    <?php endif; ?>
                                    <?php echo number_format($rejectTotal); ?>
                                </td>
                                <td class="quantity-total">
                                    <strong><?php echo number_format($totalProd); ?></strong>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <?php if($totalProd > 0): ?>
                                            <i class="fas fa-play-circle"></i> <?php echo $status; ?>
                                        <?php else: ?>
                                            <i class="fas fa-stop-circle"></i> <?php echo $status; ?>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn-details" data-bs-toggle="modal" data-bs-target="#batchModal<?php echo $i; ?>">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td><strong><i class="fas fa-chart-line"></i> GRAND TOTAL</strong></td>
                                <td><strong class="text-primary"><?php echo number_format($totalV1); ?></strong></td>
                                <td><strong class="text-primary"><?php echo number_format($totalV2); ?></strong></td>
                                <td><strong class="text-danger"><?php echo number_format($totalReject); ?></strong></td>
                                <td><strong class="text-success"><?php echo number_format($totalV1 + $totalV2); ?></strong></td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Recent Saved Reports -->
        <div class="card mt-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-history"></i> Previously Saved Reports</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Report Date</th>
                                <th>Total Batches</th>
                                <th>Version 1</th>
                                <th>Version 2</th>
                                <th>Reject</th>
                                <th>Total Records</th>
                                <th>Created By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $savedReports = $pdo->query("
                                SELECT br.*, u.full_name 
                                FROM bundle_reports br
                                LEFT JOIN users u ON br.created_by = u.id
                                ORDER BY br.created_at DESC LIMIT 10
                            ")->fetchAll();
                            ?>
                            <?php if(count($savedReports) > 0): ?>
                                <?php foreach($savedReports as $report): ?>
                                <tr>
                                    <td><i class="fas fa-calendar-alt me-2 text-primary"></i><?php echo date('F d, Y', strtotime($report['report_date'])); ?></td>
                                    <td><span class="badge bg-primary"><?php echo $report['total_batches']; ?> / 20</span></td>
                                    <td><?php echo number_format($report['total_v1']); ?></td>
                                    <td><?php echo number_format($report['total_v2']); ?></td>
                                    <td><?php echo number_format($report['total_reject']); ?></td>
                                    <td><?php echo number_format($report['total_records']); ?></td>
                                    <td><i class="fas fa-user me-1"></i><?php echo htmlspecialchars($report['full_name']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="fas fa-save fa-3x text-muted mb-3"></i>
                                        <h6>No saved reports yet</h6>
                                        <p class="text-muted">Click "Save Current Report" to save.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Batch Detail Modals -->
    <?php for($i = 1; $i <= 20; $i++): ?>
    <div class="modal fade" id="batchModal<?php echo $i; ?>" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(90deg, #667eea, #764ba2); color: white;">
                    <h5 class="modal-title"><i class="fas fa-gift"></i> Batch <?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?> - Detailed Report</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Summary for this batch -->
                    <div class="row mb-3">
                        <div class="col-md-3 col-6 mb-2">
                            <div class="alert alert-success text-center mb-0">
                                <h6><i class="fas fa-code-branch"></i> Version 1</h6>
                                <h3><?php echo number_format($batchTotals[$i]['v1']); ?></h3>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-2">
                            <div class="alert alert-info text-center mb-0">
                                <h6><i class="fas fa-code-branch"></i> Version 2</h6>
                                <h3><?php echo number_format($batchTotals[$i]['v2']); ?></h3>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-2">
                            <div class="alert alert-danger text-center mb-0">
                                <h6><i class="fas fa-times-circle"></i> Reject</h6>
                                <h3><?php echo number_format($batchTotals[$i]['reject']); ?></h3>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-2">
                            <div class="alert alert-primary text-center mb-0">
                                <h6><i class="fas fa-chart-line"></i> Total</h6>
                                <h3><?php echo number_format($batchTotals[$i]['total']); ?></h3>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Detailed Records -->
                    <h6 class="mb-3"><i class="fas fa-list"></i> All Records for Batch <?php echo $i; ?></h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Date/Time</th>
                                    <th>Version</th>
                                    <th>Type</th>
                                    <th>Quantity</th>
                                    <th>Created By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $batchRecords = array_filter($allProduction, function($item) use ($i) {
                                    return $item['batch_no'] == $i;
                                });
                                if(count($batchRecords) > 0):
                                    foreach($batchRecords as $record):
                                ?>
                                <tr>
                                    <td><i class="far fa-calendar-alt me-1"></i><?php echo date('M d, Y h:i A', strtotime($record['created_at'])); ?></td>
                                    <td>
                                        <span class="badge <?php echo $record['version'] == 'Version 1' ? 'bg-primary' : 'bg-info'; ?>">
                                            <?php echo htmlspecialchars($record['version']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $record['type'] == 'production' ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo ucfirst($record['type']); ?>
                                        </span>
                                    </td>
                                    <td><strong><?php echo $record['quantity']; ?> pcs</strong></td>
                                    <td><i class="fas fa-user me-1"></i><?php echo htmlspecialchars($record['created_by_name'] ?? 'Unknown'); ?></td>
                                </tr>
                                <?php 
                                    endforeach;
                                else:
                                ?>
                                <tr>
                                    <td colspan="5" class="text-center py-3">No records found for this batch</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <?php endfor; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>