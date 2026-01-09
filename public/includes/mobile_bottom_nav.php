<?php
/**
 * Mobile Bottom Navigation
 * App-style navigation (Mobile only)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? null;

/* CART COUNT (same table you use in header/cart) */
$cart_count = 0;
if ($user_id) {
    if ($stmt = $conn->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?")) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($cart_count);
        $stmt->fetch();
        $stmt->close();
        $cart_count = $cart_count ?? 0;
    }
}

/* ACTIVE PAGE */
$current = basename($_SERVER['PHP_SELF']);
?>

<style>
/* =========================
   MOBILE BOTTOM NAV
========================= */
.mobile-bottom-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    background: #0d0d0d;
    border-top: 1px solid #2e2e2e;
    display: flex;
    justify-content: space-around;
    align-items: center;
    padding: 8px 0 6px;
    z-index: 9999;
}

/* NAV ITEM */
.mobile-bottom-nav a {
    flex: 1;
    text-align: center;
    color: #cfcfcf;
    text-decoration: none;
    font-size: 12px;
    position: relative;
}

/* ICON */
.mobile-bottom-nav i {
    font-size: 20px;
    display: block;
    margin-bottom: 2px;
}

/* ACTIVE */
.mobile-bottom-nav a.active {
    color: #4a6fa5;
}

.mobile-bottom-nav a.active i {
    transform: scale(1.1);
}

/* CART BADGE */
.mobile-cart-badge {
    position: absolute;
    top: 2px;
    right: 30%;
    background: #dc3545;
    color: #fff;
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 50%;
}

/* SAFE AREA FOR iPHONE */
@supports (padding-bottom: env(safe-area-inset-bottom)) {
    .mobile-bottom-nav {
        padding-bottom: calc(6px + env(safe-area-inset-bottom));
    }
}

/* DESKTOP HIDE */
@media (min-width: 992px) {
    .mobile-bottom-nav {
        display: none;
    }
}

/* BODY SPACE (important) */
@media (max-width: 991px) {
    body {
        padding-bottom: 70px;
    }
}
</style>

<!-- MOBILE BOTTOM NAV -->
<nav class="mobile-bottom-nav">

    <a href="index.php" class="<?= $current == 'index.php' ? 'active' : '' ?>">
        <i class="fas fa-home"></i>
        Home
    </a>

    <a href="products.php" class="<?= $current == 'products.php' ? 'active' : '' ?>">
        <i class="fas fa-box"></i>
        Products
    </a>

    <?php if ($user_id): ?>
        <a href="wishlist.php" class="<?= $current == 'wishlist.php' ? 'active' : '' ?>">
            <i class="fas fa-heart"></i>
            Wishlist
        </a>
    <?php endif; ?>

    <a href="cart.php" class="<?= $current == 'cart.php' ? 'active' : '' ?>">
        <i class="fas fa-shopping-cart"></i>
        Cart
        <?php if ($cart_count > 0): ?>
            <span class="mobile-cart-badge">
                <?= $cart_count > 99 ? '99+' : $cart_count ?>
            </span>
        <?php endif; ?>
    </a>

    <?php if ($user_id): ?>
        <a href="logout.php" class="<?= $current == 'profile.php' ? 'active' : '' ?>">
            <i class="fas fa-user"></i>
            Profile
        </a>
    <?php else: ?>
        <a href="login.php" class="<?= $current == 'login.php' ? 'active' : '' ?>">
            <i class="fas fa-sign-in-alt"></i>
            Login
        </a>
    <?php endif; ?>

</nav>
