<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$user_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : '';

// Check if user is admin for admin-only pages
function requireAdmin() {
    global $user_role;
    if ($user_role != 'admin') {
        header("Location: ../staff/dashboard.php");
        exit();
    }
}

// Check if user is staff
function requireStaff() {
    global $user_role;
    if ($user_role != 'staff') {
        header("Location: ../admin/dashboard.php");
        exit();
    }
}
?>