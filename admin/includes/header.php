<?php
// Get admin name from session
$admin_name = $_SESSION['admin_name'] ?? 'Admin';
$admin_initial = strtoupper(substr($admin_name, 0, 1));
?>
<!-- Header -->
<div class="header">
    <div class="header-left">
        <button class="menu-toggle">
            <i class="fas fa-bars"></i>
        </button>
        <h2><i class="<?php echo $page_icon ?? 'fas fa-tachometer-alt'; ?>"></i> <?php echo $page_title ?? 'Dashboard'; ?></h2>
    </div>
    
    <div class="header-right">
        <div class="admin-profile">
            <div class="admin-avatar">
                <?php echo $admin_initial; ?>
            </div>
            <div class="admin-info">
                <h4><?php echo htmlspecialchars($admin_name); ?></h4>
                <p>Administrator</p>
            </div>
        </div>
        <button class="logout-btn" onclick="window.location.href='logout.php'">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </button>
    </div>
</div>