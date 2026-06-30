<?php
session_start();

$host = 'localhost';
$dbname = 'lombriks_inventory';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Function to get low stock products
function getLowStockProducts($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE quantity <= reorder_level ORDER BY quantity ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        return [];
    }
}

// Function to get dashboard stats
function getDashboardStats($pdo) {
    $stats = [];
    
    try {
        // Total products
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM products");
        $stats['total_products'] = $stmt->fetch()['total'];
        
        // Low stock count
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE quantity <= reorder_level");
        $stats['low_stock'] = $stmt->fetch()['total'];
        
        // Total value
        $stmt = $pdo->query("SELECT COALESCE(SUM(quantity * unit_price), 0) as total FROM products");
        $stats['total_value'] = $stmt->fetch()['total'];
        
        // Recent transactions
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM transactions WHERE DATE(transaction_date) = CURDATE()");
        $stats['today_transactions'] = $stmt->fetch()['total'];
    } catch(PDOException $e) {
        $stats['total_products'] = 0;
        $stats['low_stock'] = 0;
        $stats['total_value'] = 0;
        $stats['today_transactions'] = 0;
    }
    
    return $stats;
}

// Function to get product by ID
function getProductById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Function to update product quantity
function updateProductQuantity($pdo, $product_id, $new_quantity) {
    $stmt = $pdo->prepare("UPDATE products SET quantity = ? WHERE id = ?");
    return $stmt->execute([$new_quantity, $product_id]);
}

// Function to add transaction record
function addTransaction($pdo, $product_id, $user_id, $type, $quantity, $old_qty, $new_qty, $remarks) {
    $stmt = $pdo->prepare("INSERT INTO transactions (product_id, user_id, transaction_type, quantity, previous_quantity, new_quantity, remarks) VALUES (?, ?, ?, ?, ?, ?, ?)");
    return $stmt->execute([$product_id, $user_id, $type, $quantity, $old_qty, $new_qty, $remarks]);
}
?>