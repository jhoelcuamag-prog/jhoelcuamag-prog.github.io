<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireAdmin();

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if(!$user) {
    header("Location: users.php");
    exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    
    $update = $pdo->prepare("UPDATE users SET full_name=?, email=?, role=? WHERE id=?");
    if($update->execute([$full_name, $email, $role, $id])) {
        header("Location: users.php?success=updated");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Lombriks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f0f2f5; }
        .sidebar { width: 260px; background: linear-gradient(180deg, #2c3e50 0%, #1a252f 100%); min-height: 100vh; position: fixed; }
        .sidebar .nav-link { color: #ecf0f1; padding: 12px 20px; margin: 5px 0; border-radius: 10px; transition: all 0.3s; }
        .sidebar .nav-link:hover { background: rgba(255,255,255,0.1); transform: translateX(5px); }
        .sidebar .nav-link.active { background: linear-gradient(90deg, #667eea, #764ba2); }
        .sidebar .nav-link i { margin-right: 10px; }
        .main-content { margin-left: 260px; padding: 20px; }
        .card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
        .btn-primary { background: linear-gradient(90deg, #667eea, #764ba2); border: none; border-radius: 20px; padding: 10px 30px; }
        .form-control, .form-select { border-radius: 10px; padding: 10px 15px; }
        label { font-weight: 500; margin-bottom: 8px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="text-center py-4">
            <img src="https://cdn-icons-png.flaticon.com/512/2997/2997929.png" width="60" alt="Logo">
            <h5 class="text-white mt-2">Lombriks</h5>
            <p class="text-white-50 small">Admin Panel</p>
        </div>
        <nav class="nav flex-column px-3">
            <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a class="nav-link" href="products.php"><i class="fas fa-boxes"></i> Products</a>
            <a class="nav-link" href="stock_in.php"><i class="fas fa-arrow-down"></i> Stock In</a>
            <a class="nav-link" href="stock_out.php"><i class="fas fa-arrow-up"></i> Stock Out</a>
            <a class="nav-link active" href="users.php"><i class="fas fa-users"></i> Users</a>
            <a class="nav-link" href="transactions.php"><i class="fas fa-history"></i> Transactions</a>
            <a class="nav-link" href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
            <a class="nav-link" href="low_stock.php"><i class="fas fa-exclamation-triangle"></i> Low Stock</a>
            <a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>
    
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Edit User</h2>
            <a href="users.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
        
        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Username</label>
                            <input type="text" class="form-control" value="<?php echo $user['username']; ?>" disabled>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Full Name *</label>
                            <input type="text" name="full_name" class="form-control" value="<?php echo $user['full_name']; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" value="<?php echo $user['email']; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Role *</label>
                            <select name="role" class="form-select" required>
                                <option value="staff" <?php echo $user['role'] == 'staff' ? 'selected' : ''; ?>>Staff</option>
                                <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update User</button>
                            <a href="users.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>