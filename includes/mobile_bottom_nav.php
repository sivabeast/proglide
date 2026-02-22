<?php
/**
 * Mobile Bottom Navigation
 * Modern App-style bottom navigation for mobile devices with Category button
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Prevent multiple inclusions
if (!isset($mobile_nav_included)) {
    $mobile_nav_included = true;
    
    $user_id = $_SESSION['user_id'] ?? null;
    $user_name = $_SESSION['user_name'] ?? 'User';

    // Get cart count
    $cart_count = 0;
    $wishlist_count = 0;
    
    if ($user_id && isset($conn)) {
        // Cart count
        if ($stmt = $conn->prepare("SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE user_id = ?")) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->bind_result($cart_count);
            $stmt->fetch();
            $stmt->close();
        }
        
        // Wishlist count
        if ($stmt = $conn->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?")) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->bind_result($wishlist_count);
            $stmt->fetch();
            $stmt->close();
        }
    }

    $current = basename($_SERVER['PHP_SELF']);
    ?>

    <style>
        :root {
            --nav-bg: rgba(255, 255, 255, 0.98);
            --nav-border: rgba(0, 0, 0, 0.08);
            --nav-active: #FF3B30;
            --nav-inactive: #8E8E93;
            --nav-shadow: 0 -10px 40px rgba(0, 0, 0, 0.1);
            --badge-bg: linear-gradient(135deg, #FF3B30 0%, #FF9500 100%);
            --nav-hover: rgba(255, 59, 48, 0.1);
            --nav-glass: rgba(255, 255, 255, 0.8);
        }

        /* ====================
           MOBILE BOTTOM NAV
        ==================== */
        .mobile-bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 80px;
            background: var(--nav-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-top: 1px solid var(--nav-border);
            display: flex;
            justify-content: space-around;
            align-items: center;
            z-index: 9999;
            box-shadow: var(--nav-shadow);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* NAV ITEMS */
        .nav-item {
            flex: 1;
            text-align: center;
            position: relative;
            padding: 12px 0;
        }

        .nav-link {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            color: var(--nav-inactive);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 8px 0;
            border-radius: 16px;
            margin: 0 8px;
        }

        .nav-link:hover {
            color: var(--nav-active);
            background: var(--nav-hover);
        }

        /* ICON CONTAINER */
        .nav-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .nav-link .nav-icon i {
            font-size: 22px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* ACTIVE STATE */
        .nav-link.active {
            color: var(--nav-active);
        }

        .nav-link.active .nav-icon {
            background: linear-gradient(135deg, rgba(255, 59, 48, 0.1) 0%, rgba(255, 149, 0, 0.1) 100%);
            transform: translateY(-4px);
        }

        .nav-link.active .nav-icon i {
            color: var(--nav-active);
            transform: scale(1.1);
        }

        /* LABEL */
        .nav-label {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.3px;
            transition: all 0.3s ease;
            opacity: 0.8;
        }

        .nav-link.active .nav-label {
            opacity: 1;
            font-weight: 700;
        }

        /* BADGES */
        .nav-badge {
            position: absolute;
            top: 8px;
            right: calc(50% - 20px);
            min-width: 20px;
            height: 20px;
            background: var(--badge-bg);
            color: white;
            font-size: 11px;
            font-weight: 700;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 6px;
            border: 2px solid var(--nav-bg);
            z-index: 2;
            animation: badgePulse 2s infinite;
            box-shadow: 0 4px 12px rgba(255, 59, 48, 0.3);
        }

        @keyframes badgePulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        /* FLOATING ACTION BUTTON */
        .nav-fab {
            position: absolute;
            top: -28px;
            left: 50%;
            transform: translateX(-50%);
            width: 56px;
            height: 56px;
            background: var(--badge-bg);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            box-shadow: 0 10px 30px rgba(255, 59, 48, 0.4);
            z-index: 1;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            border: 3px solid var(--nav-bg);
            text-decoration: none;
        }

        .nav-fab:hover {
            transform: translateX(-50%) scale(1.1);
            box-shadow: 0 15px 40px rgba(255, 59, 48, 0.5);
        }

        .nav-fab:active {
            transform: translateX(-50%) scale(0.95);
        }

        /* PROFILE AVATAR */
        .profile-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #FF3B30 0%, #FF9500 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 14px;
            border: 2px solid var(--nav-bg);
            box-shadow: 0 4px 12px rgba(255, 59, 48, 0.2);
        }

        /* HOVER EFFECTS */
        .nav-link:hover .nav-icon {
            transform: translateY(-2px);
        }

        .nav-link:hover .nav-icon i {
            transform: scale(1.1);
        }

        /* CATEGORIES MENU */
        .categories-menu {
            position: absolute;
            bottom: 85px;
            left: 50%;
            transform: translateX(-50%) scale(0.9);
            background: white;
            border-radius: 20px;
            padding: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            min-width: 200px;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 9998;
            border: 1px solid var(--nav-border);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        .categories-menu.show {
            opacity: 1;
            visibility: visible;
            transform: translateX(-50%) scale(1);
        }

        .category-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 12px;
            text-decoration: none;
            color: var(--dark);
            transition: all 0.2s ease;
        }

        .category-item:hover {
            background: var(--nav-hover);
            color: var(--nav-active);
            transform: translateX(5px);
        }

        .category-item i {
            color: var(--nav-inactive);
            font-size: 18px;
            width: 24px;
        }

        .category-item:hover i {
            color: var(--nav-active);
        }

        /* SAFE AREA FOR iPHONE */
        @supports (padding-bottom: env(safe-area-inset-bottom)) {
            .mobile-bottom-nav {
                height: calc(80px + env(safe-area-inset-bottom));
                padding-bottom: env(safe-area-inset-bottom);
            }
            
            body {
                padding-bottom: calc(80px + env(safe-area-inset-bottom));
            }
        }

        /* BODY SPACING */
        @media (max-width: 991px) {
            body {
                padding-bottom: 80px;
            }
        }

        /* DESKTOP HIDE */
        @media (min-width: 992px) {
            .mobile-bottom-nav {
                display: none;
            }
            
            .categories-menu {
                display: none;
            }
        }

        /* ANIMATIONS */
        @keyframes slideUp {
            from {
                transform: translateY(100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .mobile-bottom-nav {
            animation: slideUp 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* DARK MODE SUPPORT */
        @media (prefers-color-scheme: dark) {
            :root {
                --nav-bg: rgba(28, 28, 30, 0.95);
                --nav-border: rgba(255, 255, 255, 0.08);
                --nav-active: #FF3B30;
                --nav-inactive: #8E8E93;
                --nav-hover: rgba(255, 59, 48, 0.2);
                --nav-glass: rgba(28, 28, 30, 0.8);
                --dark: #FFFFFF;
            }
            
            .categories-menu {
                background: rgba(28, 28, 30, 0.95);
                border-color: rgba(255, 255, 255, 0.1);
            }
        }

        /* RESPONSIVE ADJUSTMENTS */
        @media (max-width: 480px) {
            .nav-icon {
                width: 42px;
                height: 42px;
            }
            
            .nav-link .nav-icon i {
                font-size: 20px;
            }
            
            .nav-label {
                font-size: 10px;
            }
            
            .nav-badge {
                font-size: 10px;
                min-width: 18px;
                height: 18px;
                top: 6px;
            }
            
            .mobile-bottom-nav {
                height: 72px;
            }
            
            .categories-menu {
                min-width: 180px;
                bottom: 78px;
            }
        }
    </style>

    <!-- MOBILE BOTTOM NAVIGATION -->
    <nav class="mobile-bottom-nav">
        
        <!-- HOME -->
        <div class="nav-item">
            <a href="index.php" class="nav-link <?= $current == 'index.php' ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-home"></i>
                </div>
                <span class="nav-label">Home</span>
            </a>
        </div>

        <!-- CATEGORIES -->
        <div class="nav-item">
            <a href="javascript:void(0)" class="nav-link <?= $current == 'categories.php' ? 'active' : '' ?>" 
               id="categoriesBtn">
                <div class="nav-icon">
                    <i class="fas fa-th-large"></i>
                </div>
                <span class="nav-label">Categories</span>
            </a>
        </div>

        <!-- CART (Floating Action Button) -->
        <div class="nav-item">
            <a href="cart.php" class="nav-fab <?= $current == 'cart.php' ? 'active' : '' ?>">
                <i class="fas fa-shopping-cart"></i>
                <?php if ($cart_count > 0): ?>
                    <span class="nav-badge" style="top: -2px; right: -2px;">
                        <?= $cart_count > 99 ? '99+' : $cart_count ?>
                    </span>
                <?php endif; ?>
            </a>
        </div>

        <!-- WISHLIST -->
        <?php if ($user_id): ?>
            <div class="nav-item">
                <a href="wishlist.php" class="nav-link <?= $current == 'wishlist.php' ? 'active' : '' ?>">
                    <div class="nav-icon">
                        <i class="fas fa-heart"></i>
                        <?php if ($wishlist_count > 0): ?>
                            <span class="nav-badge"><?= $wishlist_count > 9 ? '9+' : $wishlist_count ?></span>
                        <?php endif; ?>
                    </div>
                    <span class="nav-label">Wishlist</span>
                </a>
            </div>
        <?php endif; ?>

        <!-- PROFILE/LOGIN -->
        <div class="nav-item">
            <?php if ($user_id): ?>
                <a href="profile.php" class="nav-link <?= $current == 'profile.php' ? 'active' : '' ?>">
                    <div class="nav-icon">
                        <div class="profile-avatar">
                            <?= strtoupper(substr($user_name, 0, 1)) ?>
                        </div>
                    </div>
                    <span class="nav-label">Profile</span>
                </a>
            <?php else: ?>
                <a href="login.php" class="nav-link <?= $current == 'login.php' ? 'active' : '' ?>">
                    <div class="nav-icon">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <span class="nav-label">Login</span>
                </a>
            <?php endif; ?>
        </div>

    </nav>

    <!-- CATEGORIES MENU -->
    <div class="categories-menu" id="categoriesMenu">
        <a href="products.php?category=protector" class="category-item">
            <i class="fas fa-shield-alt"></i>
            <span>Protectors</span>
        </a>
        <a href="products.php?category=back-case" class="category-item">
            <i class="fas fa-mobile-alt"></i>
            <span>Back Cases</span>
        </a>
        <a href="products.php?category=airpods" class="category-item">
            <i class="fas fa-headphones"></i>
            <span>AirPods</span>
        </a>
        <a href="products.php?category=smart-watch" class="category-item">
            <i class="fas fa-clock"></i>
            <span>Smart Watch</span>
        </a>
        <hr style="border: none; height: 1px; background: var(--nav-border); margin: 8px 0;">
        <a href="categories.php" class="category-item" style="color: var(--nav-active);">
            <i class="fas fa-th-large"></i>
            <span>All Categories</span>
        </a>
    </div>

    <script>
        // Interactive animations
        document.addEventListener('DOMContentLoaded', function() {
            const navLinks = document.querySelectorAll('.nav-link');
            const categoriesBtn = document.getElementById('categoriesBtn');
            const categoriesMenu = document.getElementById('categoriesMenu');
            const nav = document.querySelector('.mobile-bottom-nav');
            let isCategoriesOpen = false;
            
            // Categories menu toggle
            if (categoriesBtn && categoriesMenu) {
                categoriesBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    isCategoriesOpen = !isCategoriesOpen;
                    categoriesMenu.classList.toggle('show', isCategoriesOpen);
                    
                    // Add active class to categories button
                    if (isCategoriesOpen) {
                        this.classList.add('active');
                        this.querySelector('.nav-icon').style.background = 'linear-gradient(135deg, rgba(255, 59, 48, 0.1) 0%, rgba(255, 149, 0, 0.1) 100%)';
                        this.querySelector('.nav-icon').style.transform = 'translateY(-4px)';
                    } else {
                        this.classList.remove('active');
                        this.querySelector('.nav-icon').style.background = 'transparent';
                        this.querySelector('.nav-icon').style.transform = 'translateY(0)';
                    }
                });
                
                // Close categories menu when clicking outside
                document.addEventListener('click', function(e) {
                    if (!categoriesBtn.contains(e.target) && !categoriesMenu.contains(e.target)) {
                        categoriesMenu.classList.remove('show');
                        isCategoriesOpen = false;
                        categoriesBtn.classList.remove('active');
                        categoriesBtn.querySelector('.nav-icon').style.background = 'transparent';
                        categoriesBtn.querySelector('.nav-icon').style.transform = 'translateY(0)';
                    }
                });
                
                // Close categories menu on scroll
                window.addEventListener('scroll', function() {
                    categoriesMenu.classList.remove('show');
                    isCategoriesOpen = false;
                    categoriesBtn.classList.remove('active');
                    categoriesBtn.querySelector('.nav-icon').style.background = 'transparent';
                    categoriesBtn.querySelector('.nav-icon').style.transform = 'translateY(0)';
                });
            }
            
            // Add ripple effect to nav links
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    if (this === categoriesBtn) return; // Skip for categories button
                    
                    const ripple = document.createElement('span');
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size/2;
                    const y = e.clientY - rect.top - size/2;
                    
                    ripple.style.cssText = `
                        position: absolute;
                        border-radius: 50%;
                        background: rgba(255, 59, 48, 0.3);
                        transform: scale(0);
                        animation: ripple 0.6s linear;
                        width: ${size}px;
                        height: ${size}px;
                        top: ${y}px;
                        left: ${x}px;
                        pointer-events: none;
                    `;
                    
                    this.style.position = 'relative';
                    this.style.overflow = 'hidden';
                    this.appendChild(ripple);
                    
                    setTimeout(() => ripple.remove(), 600);
                });
                
                // Hover effect for touch devices
                link.addEventListener('touchstart', function() {
                    this.classList.add('hover');
                    setTimeout(() => this.classList.remove('hover'), 300);
                });
            });
            
            // Add ripple animation styles
            if (!document.querySelector('#ripple-styles')) {
                const style = document.createElement('style');
                style.id = 'ripple-styles';
                style.textContent = `
                    @keyframes ripple {
                        to {
                            transform: scale(4);
                            opacity: 0;
                        }
                    }
                    .nav-link.hover .nav-icon {
                        transform: translateY(-2px);
                    }
                `;
                document.head.appendChild(style);
            }
            
            // Hide/show navigation on scroll
            let lastScrollTop = 0;
            
            window.addEventListener('scroll', function() {
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                
                if (scrollTop > lastScrollTop && scrollTop > 100) {
                    // Scrolling down - hide nav and categories menu
                    nav.style.transform = 'translateY(100%)';
                    if (categoriesMenu) {
                        categoriesMenu.classList.remove('show');
                        isCategoriesOpen = false;
                    }
                } else {
                    // Scrolling up - show nav
                    nav.style.transform = 'translateY(0)';
                }
                
                lastScrollTop = scrollTop;
            });
            
            // Prevent hiding when clicking cart
            const fab = document.querySelector('.nav-fab');
            if (fab) {
                fab.addEventListener('click', function(e) {
                    nav.style.transform = 'translateY(0)';
                });
            }
        });
    </script>
    <?php
}
?>