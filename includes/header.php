<?php
include "db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['user_name'] ?? '';

$cart_count = 0;
$wishlist_count = 0;

if ($user_id) {
  
    $cart_stmt = $conn->prepare("SELECT COALESCE(SUM(quantity),0) FROM cart WHERE user_id=?");
    if ($cart_stmt) {
        $cart_stmt->bind_param("i", $user_id);
        $cart_stmt->execute();
        $cart_stmt->bind_result($cart_count);
        $cart_stmt->fetch();
        $cart_stmt->close();
    }
    
  
    $wish_stmt = $conn->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id=?");
    if ($wish_stmt) {
        $wish_stmt->bind_param("i", $user_id);
        $wish_stmt->execute();
        $wish_stmt->bind_result($wishlist_count);
        $wish_stmt->fetch();
        $wish_stmt->close();
    }
}

$current = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PROGLIDE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>

:root{
    --brand:#ff6b35;
    --bg-dark:#0b0b0b;
    --bg-card:#151515;
    --border:#262626;
    --text-main:#ffffff;
    --text-muted:#b5b5b5;
    --radius:12px;
    --transition:.25s ease;
}

/* =========================
   GLOBAL RESET
========================= */
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    background:#000;
    color:var(--text-main);
    padding-top:70px;
    font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
}

/* =========================
   HEADER BAR
========================= */
.header-bar{
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:70px;
    background:rgba(11,11,11,.97);
    border-bottom:1px solid var(--border);
    z-index:1000;
}

.header-inner{
    max-width:1400px;
    height:100%;
    margin:auto;
    padding:0 18px;
    display:flex;
    align-items:center;
    justify-content:space-between;
}

/* =========================
   LOGO
========================= */
.logo-section{
    display:flex;
    align-items:center;
}

.header-logo img{
    height:54px;
    width:auto;
}

/* =========================
   DESKTOP NAV
========================= */
.desktop-nav{
    display:flex;
    gap:32px;
}

.desktop-nav-link{
    color:var(--text-muted);
    text-decoration:none;
    font-size:.95rem;
    font-weight:500;
    display:flex;
    align-items:center;
    gap:8px;
    position:relative;
    transition:var(--transition);
}

.desktop-nav-link:hover{
    color:var(--text-main);
}

.desktop-nav-link.active{
    color:var(--brand);
}

.desktop-nav-link.active::after{
    content:"";
    position:absolute;
    bottom:-8px;
    left:0;
    width:100%;
    height:2px;
    background:var(--brand);
    border-radius:4px;
}

/* =========================
   HEADER ACTIONS
========================= */
.header-actions{
    display:flex;
    align-items:center;
    gap:16px;
}

/* ICON BUTTON */
.header-action-btn{
    width:40px;
    height:40px;
    border-radius:50%;
    background:var(--bg-card);
    border:1px solid var(--border);
    color:var(--text-muted);
    display:flex;
    align-items:center;
    justify-content:center;
    position:relative;
    text-decoration:none;
    transition:var(--transition);
}

.action-btn:hover{
    background:rgba(255,107,53,.1);
    border-color:var(--brand);
    color:var(--brand);
}

/* BADGE */
.action-badge{
    position:absolute;
    top:-6px;
    right:-6px;
    min-width:18px;
    height:18px;
    padding:0 5px;
    background:var(--brand);
    color:#fff;
    font-size:10px;
    font-weight:700;
    border-radius:9px;
    display:flex;
    align-items:center;
    justify-content:center;
}

/* PROFILE BUTTON */
.profile-btn{
    width:40px;
    height:40px;
    border-radius:50%;
    background:var(--brand);
    color:#fff;
    font-weight:600;
    border:none;
    cursor:pointer;
}

/* =========================
   MOBILE MENU BUTTON
========================= */
.mobile-menu-toggle{
    display:none;
    background:none;
    border:none;
    color:#fff;
    font-size:1.4rem;
}

/* =========================
   MOBILE MENU
========================= */
.mobile-menu-overlay{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.85);
    z-index:999;
}

.mobile-menu{
    position:fixed;
    top:0;
    right:-100%;
    width:300px;
    height:100%;
    background:var(--bg-dark);
    border-left:1px solid var(--border);
    z-index:1000;
    transition:.3s ease;
    overflow-y:auto;
}

.mobile-menu.active{
    right:0;
}

.mobile-menu-overlay.active{
    display:block;
}

/* MOBILE HEADER */
.mobile-header{
    padding:22px;
    background:rgba(255,107,53,.08);
    border-bottom:1px solid var(--border);
}

.mobile-user{
    display:flex;
    gap:12px;
    align-items:center;
}

.mobile-avatar{
    width:44px;
    height:44px;
    border-radius:50%;
    background:var(--brand);
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:700;
}

/* MOBILE NAV */
.mobile-nav{
    padding:10px 0;
}

.mobile-nav-item{
    display:flex;
    align-items:center;
    gap:14px;
    padding:14px 22px;
    color:var(--text-muted);
    text-decoration:none;
    border-left:3px solid transparent;
    transition:var(--transition);
}

.mobile-nav-item:hover,
.mobile-nav-item.active{
    background:rgba(255,107,53,.1);
    color:var(--brand);
    border-left-color:var(--brand);
}

/* MOBILE FOOTER */
.mobile-footer{
    padding:20px;
    border-top:1px solid var(--border);
}

.logout-btn{
    width:100%;
    padding:12px;
    border-radius:10px;
    border:1px solid var(--brand);
    background:rgba(255,107,53,.1);
    color:var(--brand);
    font-weight:600;
}

/* =========================
   RESPONSIVE BREAKPOINTS
========================= */

/* TABLET + MOBILE */
@media(max-width:991px){
    .desktop-nav{display:none;}
    .mobile-menu-toggle{display:block;}
}

/* SMALL MOBILE */
@media(max-width:576px){
    .header-inner{padding:0 14px;}
    .header-logo img{height:30px;}
    .action-btn,
    .profile-btn{
        width:34px;
        height:34px;
        font-size:.85rem;
    }
}
</style>

</head>
<body>
    <!-- HEADER -->
    <header class="header-bar">
        <div class="header-inner">
            <!-- LEFT SECTION: Logo (Mobile & Desktop) -->
            <div class="logo-section">
                <a href="index.php" class="header-logo">
                    <img src="/proglide/image/logo.png" alt="PROGLIDE">
                </a>
            </div>

            <!-- CENTER SECTION: Desktop Navigation -->
            <nav class="desktop-nav">
                <a href="index.php" class="desktop-nav-link <?= $current=='index.php'?'active':'' ?>">
                    <i class="fas fa-home"></i> Home
                </a>
                <a href="products.php" class="desktop-nav-link <?= $current=='products.php'?'active':'' ?>">
                    <i class="fas fa-box"></i> Products
                </a>
                <a href="categories.php" class="desktop-nav-link <?= $current=='categories.php'?'active':'' ?>">
                    <i class="fas fa-th-large"></i> Categories
                </a>
                
                <?php if($user_id): ?>
                    <a href="orders.php" class="desktop-nav-link <?= $current=='orders.php'?'active':'' ?>">
                        <i class="fas fa-shopping-bag"></i> Orders
                    </a>
                    <a href="wishlist.php" class="desktop-nav-link <?= $current=='wishlist.php'?'active':'' ?>">
                        <i class="fas fa-heart"></i> Wishlist
                        <?php if($wishlist_count > 0): ?>
                            <span style="font-size: 0.7rem; background: #FF6B35; color: white; padding: 2px 6px; border-radius: 10px; margin-left: 5px;"><?= $wishlist_count ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
                
                <a href="help.php" class="desktop-nav-link <?= $current=='help.php'?'active':'' ?>">
                    <i class="fas fa-question-circle"></i> Help
                </a>
            </nav>

            <!-- RIGHT SECTION: Actions + Mobile Toggle -->
            <div class="header-actions">
                

                <!-- Cart -->
                <a href="cart.php" class="action-btn header-action-btn" title="Cart">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if($cart_count > 0): ?>
                        <span class="action-badge"><?= $cart_count > 99 ? '99+' : $cart_count ?></span>
                    <?php endif; ?>
                </a>

                <!-- Profile Dropdown (Desktop) -->
                <?php if($user_id): ?>
                    <div class="profile-dropdown" id="profileDropdown">
                        <button class="profile-btn" id="profileBtn">
                            <?= strtoupper(substr($user_name, 0, 1)) ?>
                        </button>
                        <div class="dropdown-menu" id="dropdownMenu">
                            <div class="dropdown-header">
                                <div class="user-name"><?= htmlspecialchars($user_name) ?></div>
                                <div class="user-email"><?= $_SESSION['user_email'] ?? '' ?></div>
                            </div>
                            
                            <a href="logout.php" class="dropdown-item">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="action-btn header-action-btn" title="Login">
                        <i class="fas fa-sign-in-alt"></i>
                    </a>
                <?php endif; ?>

                <!-- Mobile Menu Toggle -->
                <button class="mobile-menu-toggle" id="mobileMenuToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Mobile Menu Overlay -->
    <div class="mobile-menu-overlay" id="mobileOverlay"></div>

    <!-- Mobile Menu -->
    <div class="mobile-menu" id="mobileMenu">
        <div class="mobile-header">
            <?php if($user_id): ?>
                <div class="mobile-user">
                    <div class="mobile-avatar">
                        <?= strtoupper(substr($user_name, 0, 1)) ?>
                    </div>
                    <div class="mobile-user-info">
                        <h4><?= htmlspecialchars($user_name) ?></h4>
                        <p><?= $_SESSION['user_email'] ?? '' ?></p>
                    </div>
                </div>
                <div class="mobile-stats">
                    <div class="stat-item">
                        <span class="stat-value"><?= $cart_count ?></span>
                        <span class="stat-label">Cart</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?= $wishlist_count ?></span>
                        <span class="stat-label">Wishlist</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value">0</span>
                        <span class="stat-label">Orders</span>
                    </div>
                </div>
            <?php else: ?>
                <div class="mobile-user">
                    <div class="mobile-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="mobile-user-info">
                        <h4>Guest User</h4>
                        <p>Login to access features</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <nav class="mobile-nav">
            <a href="index.php" class="mobile-nav-item <?= $current=='index.php'?'active':'' ?>">
                <i class="fas fa-home"></i> Home
            </a>
            <a href="products.php" class="mobile-nav-item <?= $current=='products.php'?'active':'' ?>">
                <i class="fas fa-box"></i> Products
            </a>
            <a href="categories.php" class="mobile-nav-item <?= $current=='categories.php'?'active':'' ?>">
                <i class="fas fa-th-large"></i> Categories
            </a>
            
            <?php if($user_id): ?>
                <a href="orders.php" class="mobile-nav-item <?= $current=='orders.php'?'active':'' ?>">
                    <i class="fas fa-shopping-bag"></i> Orders
                </a>
                <a href="wishlist.php" class="mobile-nav-item <?= $current=='wishlist.php'?'active':'' ?>">
                    <i class="fas fa-heart"></i> Wishlist
                    <?php if($wishlist_count > 0): ?>
                        <span class="badge"><?= $wishlist_count ?></span>
                    <?php endif; ?>
                </a>
                <a href="profile.php" class="mobile-nav-item <?= $current=='profile.php'?'active':'' ?>">
                    <i class="fas fa-user"></i> Profile
                </a>
                <a href="settings.php" class="mobile-nav-item">
                    <i class="fas fa-cog"></i> Settings
                </a>
            <?php else: ?>
                <a href="login.php" class="mobile-nav-item <?= $current=='login.php'?'active':'' ?>">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
                <a href="register.php" class="mobile-nav-item <?= $current=='register.php'?'active':'' ?>">
                    <i class="fas fa-user-plus"></i> Register
                </a>
            <?php endif; ?>
            
            <a href="help.php" class="mobile-nav-item <?= $current=='help.php'?'active':'' ?>">
                <i class="fas fa-question-circle"></i> Help Center
            </a>
            <a href="contact.php" class="mobile-nav-item <?= $current=='contact.php'?'active':'' ?>">
                <i class="fas fa-envelope"></i> Contact Us
            </a>
        </nav>

        <?php if($user_id): ?>
            <div class="mobile-footer">
                <button class="logout-btn" onclick="window.location.href='logout.php'">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        <?php endif; ?>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mobile Menu Toggle
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const mobileMenu = document.getElementById('mobileMenu');
        const mobileOverlay = document.getElementById('mobileOverlay');
        
        if (mobileMenuToggle && mobileMenu) {
            mobileMenuToggle.addEventListener('click', () => {
                mobileMenu.classList.toggle('active');
                mobileOverlay.classList.toggle('active');
                document.body.style.overflow = mobileMenu.classList.contains('active') ? 'hidden' : '';
                
                // Toggle icon
                mobileMenuToggle.innerHTML = mobileMenu.classList.contains('active') 
                    ? '<i class="fas fa-times"></i>' 
                    : '<i class="fas fa-bars"></i>';
            });
            
            // Close menu when clicking overlay
            mobileOverlay.addEventListener('click', () => {
                mobileMenu.classList.remove('active');
                mobileOverlay.classList.remove('active');
                document.body.style.overflow = '';
                mobileMenuToggle.innerHTML = '<i class="fas fa-bars"></i>';
            });
            
            // Close menu on link click
            mobileMenu.querySelectorAll('.mobile-nav-item, .logout-btn').forEach(link => {
                link.addEventListener('click', () => {
                    mobileMenu.classList.remove('active');
                    mobileOverlay.classList.remove('active');
                    document.body.style.overflow = '';
                    mobileMenuToggle.innerHTML = '<i class="fas fa-bars"></i>';
                });
            });
        }
        
        // Profile Dropdown
        const profileBtn = document.getElementById('profileBtn');
        const dropdownMenu = document.getElementById('dropdownMenu');
        
        if (profileBtn && dropdownMenu) {
            profileBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                dropdownMenu.classList.toggle('show');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!profileBtn.contains(e.target) && !dropdownMenu.contains(e.target)) {
                    dropdownMenu.classList.remove('show');
                }
            });
            
            // Close dropdown on link click
            dropdownMenu.querySelectorAll('.dropdown-item').forEach(link => {
                link.addEventListener('click', () => {
                    dropdownMenu.classList.remove('show');
                });
            });
        }
        
        // Search Bar Focus Effect
        const searchInput = document.querySelector('.search-input');
        if (searchInput) {
            searchInput.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            searchInput.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        }
        
        // Header Scroll Effect
        const headerBar = document.querySelector('.header-bar');
        let lastScroll = 0;
        
        window.addEventListener('scroll', () => {
            const currentScroll = window.pageYOffset;
            
            if (currentScroll > 100) {
                if (currentScroll > lastScroll) {
                    // Scrolling down - hide header
                    headerBar.style.transform = 'translateY(-100%)';
                } else {
                    // Scrolling up - show header
                    headerBar.style.transform = 'translateY(0)';
                }
            } else {
                headerBar.style.transform = 'translateY(0)';
            }
            
            lastScroll = currentScroll;
        });
    });
    </script>
</body>
</html>