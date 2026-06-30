<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

if($_SESSION['role'] != 'staff') {
    header("Location: ../admin/dashboard.php");
    exit();
}

// Create bundle_production table if not exists
$pdo->exec("CREATE TABLE IF NOT EXISTS bundle_production (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_no INT NOT NULL,
    version VARCHAR(20) NOT NULL,
    type VARCHAR(20) NOT NULL,
    quantity INT DEFAULT 0,
    production_date DATE,
    production_time TIME,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX(batch_no, version, type)
)");

// Handle save for Version 1
if(isset($_POST['save_v1'])) {
    $batch_no = $_POST['batch_no'];
    $quantity = $_POST['v1_quantity'];
    $date = $_POST['v1_date'];
    $time = $_POST['v1_time'];
    
    $stmt = $pdo->prepare("INSERT INTO bundle_production (batch_no, version, type, quantity, production_date, production_time, created_by) VALUES (?, 'Version 1', 'production', ?, ?, ?, ?)");
    $stmt->execute([$batch_no, $quantity, $date, $time, $_SESSION['user_id']]);
    $success = "Batch $batch_no - Version 1 saved successfully!";
}

// Handle save for Version 2
if(isset($_POST['save_v2'])) {
    $batch_no = $_POST['batch_no'];
    $quantity = $_POST['v2_quantity'];
    $date = $_POST['v2_date'];
    $time = $_POST['v2_time'];
    
    $stmt = $pdo->prepare("INSERT INTO bundle_production (batch_no, version, type, quantity, production_date, production_time, created_by) VALUES (?, 'Version 2', 'production', ?, ?, ?, ?)");
    $stmt->execute([$batch_no, $quantity, $date, $time, $_SESSION['user_id']]);
    $success = "Batch $batch_no - Version 2 saved successfully!";
}

// Handle save for Reject
if(isset($_POST['save_reject'])) {
    $batch_no = $_POST['batch_no'];
    $quantity = $_POST['reject_quantity'];
    $version = $_POST['reject_version'];
    
    $stmt = $pdo->prepare("INSERT INTO bundle_production (batch_no, version, type, quantity, created_by) VALUES (?, ?, 'reject', ?, ?)");
    $stmt->execute([$batch_no, $version, $quantity, $_SESSION['user_id']]);
    $success = "Batch $batch_no - $version Reject saved successfully!";
}

// Handle Delete for Version 1
if(isset($_POST['delete_v1'])) {
    $batch_no = $_POST['batch_no'];
    $record_id = $_POST['record_id'];
    
    $stmt = $pdo->prepare("DELETE FROM bundle_production WHERE id = ? AND batch_no = ? AND version = 'Version 1' AND type = 'production'");
    $stmt->execute([$record_id, $batch_no]);
    $success = "Batch $batch_no - Version 1 deleted successfully!";
}

// Handle Delete for Version 2
if(isset($_POST['delete_v2'])) {
    $batch_no = $_POST['batch_no'];
    $record_id = $_POST['record_id'];
    
    $stmt = $pdo->prepare("DELETE FROM bundle_production WHERE id = ? AND batch_no = ? AND version = 'Version 2' AND type = 'production'");
    $stmt->execute([$record_id, $batch_no]);
    $success = "Batch $batch_no - Version 2 deleted successfully!";
}

// Handle Delete for Reject
if(isset($_POST['delete_reject'])) {
    $batch_no = $_POST['batch_no'];
    $record_id = $_POST['record_id'];
    
    $stmt = $pdo->prepare("DELETE FROM bundle_production WHERE id = ? AND batch_no = ? AND type = 'reject'");
    $stmt->execute([$record_id, $batch_no]);
    $success = "Batch $batch_no - Reject deleted successfully!";
}

// Handle Add New Record for Version 1
if(isset($_POST['add_v1'])) {
    $batch_no = $_POST['batch_no'];
    $quantity = $_POST['v1_quantity_new'];
    $date = $_POST['v1_date_new'];
    $time = $_POST['v1_time_new'];
    
    $stmt = $pdo->prepare("INSERT INTO bundle_production (batch_no, version, type, quantity, production_date, production_time, created_by) VALUES (?, 'Version 1', 'production', ?, ?, ?, ?)");
    $stmt->execute([$batch_no, $quantity, $date, $time, $_SESSION['user_id']]);
    $success = "Batch $batch_no - New Version 1 added successfully!";
}

// Handle Add New Record for Version 2
if(isset($_POST['add_v2'])) {
    $batch_no = $_POST['batch_no'];
    $quantity = $_POST['v2_quantity_new'];
    $date = $_POST['v2_date_new'];
    $time = $_POST['v2_time_new'];
    
    $stmt = $pdo->prepare("INSERT INTO bundle_production (batch_no, version, type, quantity, production_date, production_time, created_by) VALUES (?, 'Version 2', 'production', ?, ?, ?, ?)");
    $stmt->execute([$batch_no, $quantity, $date, $time, $_SESSION['user_id']]);
    $success = "Batch $batch_no - New Version 2 added successfully!";
}

// Handle Add New Record for Reject
if(isset($_POST['add_reject'])) {
    $batch_no = $_POST['batch_no'];
    $quantity = $_POST['reject_quantity_new'];
    $version = $_POST['reject_version_new'];
    
    $stmt = $pdo->prepare("INSERT INTO bundle_production (batch_no, version, type, quantity, created_by) VALUES (?, ?, 'reject', ?, ?)");
    $stmt->execute([$batch_no, $version, $quantity, $_SESSION['user_id']]);
    $success = "Batch $batch_no - New $version Reject added successfully!";
}

// Get saved data for each batch
$savedData = [];
for($i = 1; $i <= 20; $i++) {
    $stmt = $pdo->prepare("SELECT id, version, type, quantity, production_date, production_time FROM bundle_production WHERE batch_no = ? ORDER BY created_at DESC");
    $stmt->execute([$i]);
    $savedData[$i] = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bundle Production - Lombriks</title>
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
        .batch-header { background: linear-gradient(90deg, #2c3e50, #34495e); color: white; padding: 10px 20px; border-radius: 15px 15px 0 0; cursor: pointer; }
        .batch-header h4 { margin: 0; font-size: 18px; }
        .batch-body { padding: 20px; background: white; border-radius: 0 0 15px 15px; }
        .version-card { border: 1px solid #e0e0e0; border-radius: 12px; padding: 15px; margin-bottom: 15px; background: #fafafa; }
        .version-title { font-weight: 600; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid; }
        .version-v1 .version-title { color: #667eea; border-bottom-color: #667eea; }
        .version-v2 .version-title { color: #764ba2; border-bottom-color: #764ba2; }
        .version-reject .version-title { color: #e74c3c; border-bottom-color: #e74c3c; }
        .btn-save { background: linear-gradient(90deg, #667eea, #764ba2); color: white; border: none; border-radius: 20px; padding: 8px 25px; }
        .btn-save:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102,126,234,0.4); }
        .btn-reject-save { background: linear-gradient(90deg, #e74c3c, #c0392b); color: white; border: none; border-radius: 20px; padding: 8px 25px; }
        .btn-add { background: #27ae60; color: white; border: none; border-radius: 20px; padding: 5px 15px; font-size: 12px; }
        .btn-add:hover { background: #219a52; }
        .btn-delete { background: #e74c3c; color: white; border: none; border-radius: 20px; padding: 2px 10px; font-size: 11px; margin-left: 5px; }
        .btn-delete:hover { background: #c0392b; }
        .saved-item { background: #f0f0f0; border-radius: 8px; padding: 8px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center; }
        .batch-number { background: #667eea; color: white; width: 35px; height: 35px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; margin-right: 10px; }
        .collapsible { cursor: pointer; }
        .collapsible:hover { opacity: 0.9; }
        .add-form { margin-top: 15px; padding-top: 15px; border-top: 1px dashed #ddd; }
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
            <a class="nav-link active" href="bundles.php"><i class="fas fa-gift"></i> <span>Bundle List</span></a>
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
            <h2><i class="fas fa-gift text-primary"></i> Bundle Production Schedule</h2>
            <div><span class="badge bg-primary p-2">20 Batches Total</span></div>
        </div>
        
        <?php if(isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Batch 1 to 20 -->
        <?php for($batch = 1; $batch <= 20; $batch++): ?>
        <div class="card" id="batch<?php echo $batch; ?>">
            <div class="batch-header collapsible" onclick="toggleBatch(<?php echo $batch; ?>)">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="batch-number"><?php echo str_pad($batch, 2, '0', STR_PAD_LEFT); ?></span>
                        <h4 class="d-inline">BATCH <?php echo $batch; ?></h4>
                    </div>
                    <i class="fas fa-chevron-down" id="icon<?php echo $batch; ?>"></i>
                </div>
            </div>
            <div class="batch-body" id="batchBody<?php echo $batch; ?>" style="display: <?php echo $batch <= 5 ? 'block' : 'none'; ?>;">
                <div class="row">
                    <!-- Version 1 -->
                    <div class="col-md-4">
                        <div class="version-card version-v1">
                            <div class="version-title"><i class="fas fa-code-branch"></i> Version 1</div>
                            <?php 
                            $v1Saved = array_filter($savedData[$batch], function($item) {
                                return $item['version'] == 'Version 1' && $item['type'] == 'production';
                            });
                            foreach($v1Saved as $saved):
                            ?>
                            <div class="saved-item">
                                <div>
                                    <strong><?php echo $saved['quantity']; ?> pcs</strong><br>
                                    <small><?php echo date('M d, Y', strtotime($saved['production_date'])); ?> <?php echo date('h:i A', strtotime($saved['production_time'])); ?></small>
                                </div>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="batch_no" value="<?php echo $batch; ?>">
                                    <input type="hidden" name="record_id" value="<?php echo $saved['id']; ?>">
                                    <button type="submit" name="delete_v1" class="btn-delete" onclick="return confirm('Delete this record?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                            <?php endforeach; ?>
                            
                            <div class="add-form">
                                <form method="POST">
                                    <input type="hidden" name="batch_no" value="<?php echo $batch; ?>">
                                    <div class="mb-2"><label class="form-label small">Add New - Quantity</label><input type="number" name="v1_quantity_new" class="form-control form-control-sm" placeholder="Enter quantity" required min="0"></div>
                                    <div class="mb-2"><label class="form-label small">Date</label><input type="date" name="v1_date_new" class="form-control form-control-sm" required></div>
                                    <div class="mb-2"><label class="form-label small">Time</label><input type="time" name="v1_time_new" class="form-control form-control-sm" required></div>
                                    <button type="submit" name="add_v1" class="btn-add w-100"><i class="fas fa-plus"></i> Add New Record</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Version 2 -->
                    <div class="col-md-4">
                        <div class="version-card version-v2">
                            <div class="version-title"><i class="fas fa-code-branch"></i> Version 2</div>
                            <?php 
                            $v2Saved = array_filter($savedData[$batch], function($item) {
                                return $item['version'] == 'Version 2' && $item['type'] == 'production';
                            });
                            foreach($v2Saved as $saved):
                            ?>
                            <div class="saved-item">
                                <div>
                                    <strong><?php echo $saved['quantity']; ?> pcs</strong><br>
                                    <small><?php echo date('M d, Y', strtotime($saved['production_date'])); ?> <?php echo date('h:i A', strtotime($saved['production_time'])); ?></small>
                                </div>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="batch_no" value="<?php echo $batch; ?>">
                                    <input type="hidden" name="record_id" value="<?php echo $saved['id']; ?>">
                                    <button type="submit" name="delete_v2" class="btn-delete" onclick="return confirm('Delete this record?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                            <?php endforeach; ?>
                            
                            <div class="add-form">
                                <form method="POST">
                                    <input type="hidden" name="batch_no" value="<?php echo $batch; ?>">
                                    <div class="mb-2"><label class="form-label small">Add New - Quantity</label><input type="number" name="v2_quantity_new" class="form-control form-control-sm" placeholder="Enter quantity" required min="0"></div>
                                    <div class="mb-2"><label class="form-label small">Date</label><input type="date" name="v2_date_new" class="form-control form-control-sm" required></div>
                                    <div class="mb-2"><label class="form-label small">Time</label><input type="time" name="v2_time_new" class="form-control form-control-sm" required></div>
                                    <button type="submit" name="add_v2" class="btn-add w-100"><i class="fas fa-plus"></i> Add New Record</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Reject -->
                    <div class="col-md-4">
                        <div class="version-card version-reject">
                            <div class="version-title"><i class="fas fa-times-circle"></i> Reject</div>
                            <?php 
                            $rejectSaved = array_filter($savedData[$batch], function($item) {
                                return $item['type'] == 'reject';
                            });
                            foreach($rejectSaved as $saved):
                            ?>
                            <div class="saved-item">
                                <div>
                                    <strong><?php echo $saved['version']; ?> - <?php echo $saved['quantity']; ?> pcs</strong>
                                </div>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="batch_no" value="<?php echo $batch; ?>">
                                    <input type="hidden" name="record_id" value="<?php echo $saved['id']; ?>">
                                    <button type="submit" name="delete_reject" class="btn-delete" onclick="return confirm('Delete this record?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                            <?php endforeach; ?>
                            
                            <div class="add-form">
                                <form method="POST">
                                    <input type="hidden" name="batch_no" value="<?php echo $batch; ?>">
                                    <div class="mb-2"><label class="form-label small">Select Version</label><select name="reject_version_new" class="form-select form-select-sm" required><option value="">Select Version</option><option value="Version 1">Version 1</option><option value="Version 2">Version 2</option></select></div>
                                    <div class="mb-2"><label class="form-label small">Reject Quantity</label><input type="number" name="reject_quantity_new" class="form-control form-control-sm" placeholder="Enter reject quantity" required min="0"></div>
                                    <button type="submit" name="add_reject" class="btn-add w-100"><i class="fas fa-plus"></i> Add New Record</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endfor; ?>
        
        <!-- Summary Section -->
        <div class="card mt-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Production Summary</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php
                    $totalV1 = $pdo->query("SELECT SUM(quantity) as total FROM bundle_production WHERE version = 'Version 1' AND type = 'production'")->fetch()['total'] ?? 0;
                    $totalV2 = $pdo->query("SELECT SUM(quantity) as total FROM bundle_production WHERE version = 'Version 2' AND type = 'production'")->fetch()['total'] ?? 0;
                    $totalReject = $pdo->query("SELECT SUM(quantity) as total FROM bundle_production WHERE type = 'reject'")->fetch()['total'] ?? 0;
                    $totalBatches = $pdo->query("SELECT COUNT(DISTINCT batch_no) as total FROM bundle_production")->fetch()['total'] ?? 0;
                    $totalRecords = $pdo->query("SELECT COUNT(*) as total FROM bundle_production")->fetch()['total'] ?? 0;
                    ?>
                    <div class="col-md-3 text-center"><div class="alert alert-primary"><h6>Total Batches</h6><h3><?php echo $totalBatches; ?> / 20</h3></div></div>
                    <div class="col-md-3 text-center"><div class="alert alert-success"><h6>Total Version 1</h6><h3><?php echo number_format($totalV1); ?> pcs</h3></div></div>
                    <div class="col-md-3 text-center"><div class="alert alert-info"><h6>Total Version 2</h6><h3><?php echo number_format($totalV2); ?> pcs</h3></div></div>
                    <div class="col-md-3 text-center"><div class="alert alert-danger"><h6>Total Reject</h6><h3><?php echo number_format($totalReject); ?> pcs</h3></div></div>
                </div>
                <div class="row mt-2">
                    <div class="col-12 text-center"><div class="alert alert-secondary"><h6>Total Records Saved</h6><h3><?php echo $totalRecords; ?> records</h3></div></div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function toggleBatch(batchNo) {
            const body = document.getElementById('batchBody' + batchNo);
            const icon = document.getElementById('icon' + batchNo);
            if(body.style.display === 'none') {
                body.style.display = 'block';
                icon.classList.remove('fa-chevron-right');
                icon.classList.add('fa-chevron-down');
            } else {
                body.style.display = 'none';
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-right');
            }
        }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>