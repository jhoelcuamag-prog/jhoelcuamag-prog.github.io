<?php
// This file contains the mobile-responsive sidebar with hamburger menu
// Include this in all admin pages
?>
<!-- ===== HAMBURGER MENU BUTTON ===== -->
<button class="hamburger-btn" id="hamburgerBtn" onclick="toggleSidebar()">
    <i class="fas fa-bars"></i>
</button>

<!-- ===== SIDEBAR OVERLAY ===== -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- ===== SIDEBAR ===== -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <img src="../uploads/logo.jpg" alt="Lombriks Logo" onerror="this.src='https://cdn-icons-png.flaticon.com/512/2997/2997929.png'">
        <h5>LOMBRIKS</h5>
        <p>Inventory System</p>
    </div>
    
    <div class="sidebar-title">MAIN NAVIGATION</div>
    <nav class="nav flex-column">
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
            <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>" href="products.php">
            <i class="fas fa-boxes"></i> <span>Products</span>
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'stock_in.php' ? 'active' : ''; ?>" href="stock_in.php">
            <i class="fas fa-arrow-down"></i> <span>Stock In</span>
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'stock_out.php' ? 'active' : ''; ?>" href="stock_out.php">
            <i class="fas fa-arrow-up"></i> <span>Stock Out</span>
        </a>
    </nav>
    
    <div class="sidebar-title">FINANCIAL</div>
    <nav class="nav flex-column">
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'financial.php' ? 'active' : ''; ?>" href="financial.php">
            <i class="fas fa-chart-line"></i> <span>Financial Management</span>
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'add_income.php' ? 'active' : ''; ?>" href="add_income.php">
            <i class="fas fa-plus-circle"></i> <span>Add Income</span>
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'add_expense.php' ? 'active' : ''; ?>" href="add_expense.php">
            <i class="fas fa-minus-circle"></i> <span>Add Expense</span>
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'financial_reports.php' ? 'active' : ''; ?>" href="financial_reports.php">
            <i class="fas fa-file-alt"></i> <span>Financial Reports</span>
        </a>
    </nav>
    
    <div class="sidebar-title">MANAGEMENT</div>
    <nav class="nav flex-column">
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>" href="users.php">
            <i class="fas fa-users"></i> <span>User Management</span>
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'bundle_reports.php' ? 'active' : ''; ?>" href="bundle_reports.php">
            <i class="fas fa-chart-pie"></i> <span>Bundle Reports</span>
        </a>
    </nav>
    
    <div class="sidebar-title">REPORTS & ANALYTICS</div>
    <nav class="nav flex-column">
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'transactions.php' ? 'active' : ''; ?>" href="transactions.php">
            <i class="fas fa-history"></i> <span>Transactions</span>
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>" href="reports.php">
            <i class="fas fa-chart-bar"></i> <span>Inventory Reports</span>
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'low_stock.php' ? 'active' : ''; ?>" href="low_stock.php">
            <i class="fas fa-exclamation-triangle"></i> <span>Low Stock Alert</span>
        </a>
    </nav>
    
    <div class="sidebar-title">ACCOUNT</div>
    <nav class="nav flex-column">
        <a class="nav-link text-danger" href="../logout.php">
            <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
        </a>
    </nav>
    
    <div class="sidebar-bottom">
        <small><span class="online-status"></span> System Online</small>
    </div>
</div>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const hamburger = document.getElementById('hamburgerBtn');
        
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
        
        if (sidebar.classList.contains('active')) {
            hamburger.innerHTML = '<i class="fas fa-times"></i>';
        } else {
            hamburger.innerHTML = '<i class="fas fa-bars"></i>';
        }
    }
    
    function closeSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const hamburger = document.getElementById('hamburgerBtn');
        
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
        hamburger.innerHTML = '<i class="fas fa-bars"></i>';
    }
    
    // Close sidebar when window resizes to desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth > 992) {
            closeSidebar();
        }
    });
</script>