<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireAdmin();

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_code = $_POST['product_code'];
    $product_name = $_POST['product_name'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $quantity = $_POST['quantity'];
    $reorder_level = $_POST['reorder_level'];
    $unit_price = $_POST['unit_price'];
    
    // Handle image upload for Mac
    $product_image = 'default-product.png';
    
    if(isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['product_image']['name'];
        $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $filesize = $_FILES['product_image']['size'];
        $tmp_name = $_FILES['product_image']['tmp_name'];
        
        // Debug: Check if file is uploaded
        if(empty($tmp_name)) {
            $error = "No file uploaded or file too large.";
        } elseif(!in_array($filetype, $allowed)) {
            $error = "Invalid file type. Allowed: " . implode(', ', $allowed);
        } elseif($filesize > 2 * 1024 * 1024) {
            $error = "File too large. Max 2MB allowed.";
        } else {
            // Create unique filename
            $new_filename = time() . '_' . uniqid() . '.' . $filetype;
            
            // Create directory if not exists
            $upload_dir = '../uploads/products/';
            if(!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $upload_path = $upload_dir . $new_filename;
            
            // Move uploaded file
            if(move_uploaded_file($tmp_name, $upload_path)) {
                $product_image = $new_filename;
            } else {
                $error = "Failed to move uploaded file. Check folder permissions.";
            }
        }
    }
    
    // Check if product code exists
    $check = $pdo->prepare("SELECT id FROM products WHERE product_code = ?");
    $check->execute([$product_code]);
    if($check->rowCount() > 0) {
        $error = "Product code already exists!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO products (product_code, product_name, description, category, quantity, reorder_level, unit_price, product_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if($stmt->execute([$product_code, $product_name, $description, $category, $quantity, $reorder_level, $unit_price, $product_image])) {
            header("Location: products.php?success=added");
            exit();
        } else {
            $error = "Failed to add product to database";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Lombriks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f0f2f5; }
        .sidebar { width: 260px; background: linear-gradient(180deg, #2c3e50 0%, #1a252f 100%); min-height: 100vh; position: fixed; left: 0; top: 0; }
        .sidebar .nav-link { color: #ecf0f1; padding: 12px 20px; margin: 5px 0; border-radius: 10px; transition: all 0.3s; }
        .sidebar .nav-link:hover { background: rgba(255,255,255,0.1); transform: translateX(5px); }
        .sidebar .nav-link.active { background: linear-gradient(90deg, #667eea, #764ba2); }
        .sidebar .nav-link i { margin-right: 10px; width: 20px; }
        .main-content { margin-left: 260px; padding: 20px; }
        .card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); transition: transform 0.3s; }
        .card:hover { transform: translateY(-5px); }
        .btn-primary { background: linear-gradient(90deg, #667eea, #764ba2); border: none; border-radius: 20px; padding: 10px 30px; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102,126,234,0.4); }
        .btn-secondary { background: #6c757d; border: none; border-radius: 20px; padding: 10px 30px; }
        .form-control, .form-select { border-radius: 10px; padding: 10px 15px; border: 1px solid #ddd; }
        label { font-weight: 500; margin-bottom: 8px; color: #2c3e50; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in { animation: fadeIn 0.5s ease-out; }
        .required-field::after { content: "*"; color: red; margin-left: 5px; }
        .image-preview {
            width: 150px;
            height: 150px;
            border: 2px dashed #ddd;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 10px;
            overflow: hidden;
            background: #f8f9fa;
        }
        .image-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
        }
        .upload-area {
            border: 2px dashed #667eea;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s;
        }
        .upload-area:hover {
            background: #e9ecef;
            border-color: #764ba2;
        }
        .upload-area i {
            font-size: 48px;
            color: #667eea;
            margin-bottom: 10px;
        }
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
            <a class="nav-link active" href="products.php"><i class="fas fa-boxes"></i> Products</a>
            <a class="nav-link" href="stock_in.php"><i class="fas fa-arrow-down"></i> Stock In</a>
            <a class="nav-link" href="stock_out.php"><i class="fas fa-arrow-up"></i> Stock Out</a>
            <a class="nav-link" href="users.php"><i class="fas fa-users"></i> Users</a>
            <a class="nav-link" href="transactions.php"><i class="fas fa-history"></i> Transactions</a>
            <a class="nav-link" href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
            <a class="nav-link" href="low_stock.php"><i class="fas fa-exclamation-triangle"></i> Low Stock</a>
            <a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>
    
    <div class="main-content fade-in">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-plus-circle text-primary"></i> Add New Product</h2>
            <a href="products.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
        
        <div class="card">
            <div class="card-header bg-white border-0 pt-4">
                <h5 class="mb-0">Product Information</h5>
                <small class="text-muted">Fill in all the required fields below</small>
            </div>
            <div class="card-body">
                <?php if(isset($error) && $error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="required-field">Product Code</label>
                            <input type="text" name="product_code" class="form-control" placeholder="Enter product code" required>
                            <small class="text-muted">Example: PRD-001</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="required-field">Product Name</label>
                            <input type="text" name="product_name" class="form-control" placeholder="Enter product name" required>
                        </div>
                        <div class="col-12 mb-3">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Product description (optional)"></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="required-field">Category</label>
                            <select name="category" class="form-select" required>
                                <option value="">Select Category</option>
                                <option value="Version 1">Version 1</option>
                                <option value="Version 2">Version 2</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="required-field">Initial Quantity</label>
                            <input type="number" name="quantity" class="form-control" required min="0" value="0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="required-field">Reorder Level</label>
                            <input type="number" name="reorder_level" class="form-control" required min="1" value="10">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="required-field">Unit Price (₱)</label>
                            <input type="number" step="0.01" name="unit_price" class="form-control" placeholder="0.00" required>
                        </div>
                        
                        <!-- Image Upload Section for Mac -->
                        <div class="col-md-6 mb-3">
                            <label>Product Image</label>
                            <div class="upload-area" onclick="document.getElementById('product_image').click()">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p class="mb-0">Click to upload product image</p>
                                <small class="text-muted">JPG, PNG, GIF (Max 2MB)</small>
                                <input type="file" name="product_image" id="product_image" accept="image/jpeg,image/png,image/gif,image/webp" style="display: none;" onchange="previewImage(this)">
                            </div>
                            <div id="imagePreview" class="image-preview" style="display: none;">
                                <img id="previewImg" src="" alt="Preview">
                            </div>
                        </div>
                        
                        <div class="col-12 mt-3">
                            <hr>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Product</button>
                            <a href="products.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            const previewImg = document.getElementById('previewImg');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    preview.style.display = 'flex';
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>