<?php
// Get current page name for active class
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Sidebar -->
<div class="sidebar">
    <div class="logo">
        <h1>PROGLIDE</h1>
        <p>Admin Dashboard</p>
    </div>
    
    <ul class="nav-menu">
        <li class="nav-item">
            <a href="index.php" class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a href="products.php" class="nav-link <?php echo in_array($current_page, ['products.php', 'add_product.php', 'edit_product.php']) ? 'active' : ''; ?>">
                <i class="fas fa-box"></i>
                <span>Products</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a href="add_product.php" class="nav-link <?php echo $current_page == 'add_product.php' ? 'active' : ''; ?>">
                <i class="fas fa-plus-circle"></i>
                <span>Add Product</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a href="categories.php" class="nav-link <?php echo $current_page == 'categories.php' ? 'active' : ''; ?>">
                <i class="fas fa-layer-group"></i>
                <span>Categories</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a href="orders.php" class="nav-link <?php echo $current_page == 'orders.php' ? 'active' : ''; ?>">
                <i class="fas fa-shopping-cart"></i>
                <span>Orders</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a href="users.php" class="nav-link <?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>Users</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a href="brands.php" class="nav-link <?php echo $current_page == 'brands.php' ? 'active' : ''; ?>">
                <i class="fas fa-tag"></i>
                <span>Brands</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a href="phone_models.php" class="nav-link <?php echo $current_page == 'phone_models.php' ? 'active' : ''; ?>">
                <i class="fas fa-mobile-alt"></i>
                <span>Phone Models</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a href="material_types.php" class="nav-link <?php echo $current_page == 'material_types.php' ? 'active' : ''; ?>">
                <i class="fas fa-cube"></i>
                <span>Materials</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a href="variant_types.php" class="nav-link <?php echo $current_page == 'variant_types.php' ? 'active' : ''; ?>">
                <i class="fas fa-paint-bucket"></i>
                <span>Variants</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a href="settings.php" class="nav-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a href="profile.php" class="nav-link <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user"></i>
                <span>My Profile</span>
            </a>
        </li>
    </ul>
</div>