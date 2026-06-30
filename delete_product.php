<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireAdmin();

$id = $_GET['id'];
$stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
$stmt->execute([$id]);

header("Location: products.php?success=deleted");
exit();
?>