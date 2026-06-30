<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireAdmin();

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if(!$product) {
    header("Location: products.php");
    exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_name = $_POST['product_name'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $quantity = $_POST['quantity'];
    $reorder_level = $_POST['reorder_level'];
    $unit_price = $_POST['unit_price'];
    $product_image = $product['product_image']; // Keep existing image
    
    // Handle image upload
    if(isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['product_image']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        $filesize = $_FILES['product_image']['size'];
        
        if(in_array(strtolower($filetype), $allowed)) {
            if($filesize <= 2 * 1024 * 1024) {
                $new_filename = time() . '_' . uniqid() . '.' . $filetype;
                $upload_path = '../uploads/products/' . $new_filename;
                
                if(!is_dir('../uploads/products/')) {
                    mkdir('../uploads/products/', 0777, true);
                }
                
                if(move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                    // Delete old image if not default
                    if($product['product_image'] != 'default-product.png' && file_exists('../uploads/products/' . $product['product_image'])) {
                        unlink('../uploads/products/' . $product['product_image']);
                    }
                    $product_image = $new_filename;
                }
            }
        }
    }
    
    // Handle remove image
    if(isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
        if($product['product_image'] != 'default-product.png' && file_exists('../uploads/products/' . $product['product_image'])) {
            unlink('../uploads/products/' . $product['product_image']);
        }
        $product_image = 'default-product.png';
    }
    
    $update = $pdo->prepare("UPDATE products SET product_name=?, description=?, category=?, quantity=?, reorder_level=?, unit_price=?, product_image=? WHERE id=?");
    if($update->execute([$product_name, $description, $category, $quantity, $reorder_level, $unit_price, $product_image, $id])) {
        header("Location: products.php?success=updated");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Lombriks</title>
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
        .card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
        .btn-primary { background: linear-gradient(90deg, #667eea, #764ba2); border: none; border-radius: 20px; padding: 10px 30px; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102,126,234,0.4); }
        .form-control, .form-select { border-radius: 10px; padding: 10px 15px; }
        label { font-weight: 500; margin-bottom: 8px; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in { animation: fadeIn 0.5s ease-out; }
        .current-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 10px;
            border: 2px solid #ddd;
        }
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
            <h2><i class="fas fa-edit text-warning"></i> Edit Product</h2>
            <a href="products.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Products</a>
        </div>
        
        <div class="card">
            <div class="card-header bg-white border-0 pt-4">
                <h5 class="mb-0">Edit Product Information</h5>
                <small class="text-muted">Update the product details below</small>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Product Code</label>
                            <input type="text" class="form-control" value="<?php echo $product['product_code']; ?>" disabled>
                            <small class="text-muted">Product code cannot be changed</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="required-field">Product Name *</label>
                            <input type="text" name="product_name" class="form-control" value="<?php echo htmlspecialchars($product['product_name']); ?>" required>
                        </div>
                        <div class="col-12 mb-3">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($product['description']); ?></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="required-field">Category *</label>
                            <select name="category" class="form-select" required>
                                <option value="">Select Category</option>
                                <option value="Version 1" <?php echo $product['category'] == 'Version 1' ? 'selected' : ''; ?>>Version 1</option>
                                <option value="Version 2" <?php echo $product['category'] == 'Version 2' ? 'selected' : ''; ?>>Version 2</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="required-field">Quantity *</label>
                            <input type="number" name="quantity" class="form-control" value="<?php echo $product['quantity']; ?>" required min="0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="required-field">Reorder Level *</label>
                            <input type="number" name="reorder_level" class="form-control" value="<?php echo $product['reorder_level']; ?>" required min="1">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="required-field">Unit Price (₱) *</label>
                            <input type="number" step="0.01" name="unit_price" class="form-control" value="<?php echo $product['unit_price']; ?>" required>
                        </div>
                        
                        <!-- Current Image Display -->
                        <div class="col-md-6 mb-3">
                            <label>Current Product Image</label>
                            <div>
                                <?php 
                                $image_path = '../uploads/products/' . $product['product_image'];
                                if($product['product_image'] != 'default-product.png' && file_exists($image_path)) {
                                    echo '<img src="' . $image_path . '" class="current-image" alt="Product Image">';
                                } else {
                                    echo '<img src="https://via.placeholder.com/100" class="current-image" alt="No Image">';
                                }
                                ?>
                            </div>
                            <div class="form-check mt-2">
                                <input type="checkbox" name="remove_image" value="1" class="form-check-input" id="removeImage">
                                <label class="form-check-label text-danger" for="removeImage">
                                    <i class="fas fa-trash"></i> Remove current image
                                </label>
                            </div>
                        </div>
                        
                        <!-- New Image Upload -->
                        <div class="col-md-6 mb-3">
                            <label>Upload New Image</label>
                            <input type="file" name="product_image" class="form-control" accept="image/*" onchange="previewImage(this)">
                            <small class="text-muted">JPG, JPEG, PNG, GIF, WEBP (Max 2MB)</small>
                            <div id="imagePreview" class="image-preview" style="display: none;">
                                <img id="previewImg" src="" alt="Preview">
                            </div>
                        </div>
                        
                        <div class="col-12 mt-3">
                            <hr>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Product</button>
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