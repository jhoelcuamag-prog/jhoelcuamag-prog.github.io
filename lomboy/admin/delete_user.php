<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireAdmin();

$id = $_GET['id'];
if($id != $_SESSION['user_id']) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: users.php?success=deleted");
exit();
?>