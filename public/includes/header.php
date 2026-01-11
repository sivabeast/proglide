<?php
include "db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? null;

/* =========================
   CART COUNT
========================= */
$cart_count = 0;
if ($user_id) {
    if ($stmt = $conn->prepare("SELECT COALESCE(SUM(quantity),0) FROM cart WHERE user_id=?")) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($cart_count);
        $stmt->fetch();
        $stmt->close();
    }
}

$current = basename($_SERVER['PHP_SELF']);
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<style>
/* =========================
   BODY OFFSET
========================= */
body{
    padding-top:72px;
}

/* =========================
   HEADER BAR
========================= */
.header-bar{
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:72px;
    background:#0b0b0b;
    border-bottom:1px solid #1f1f1f;
    z-index:3000;
}

/* =========================
   INNER WRAPPER
========================= */
.header-inner{
    height:100%;
    max-width:1280px;
    margin:auto;
    padding:0 16px;
    display:flex;
    align-items:center;
    justify-content:space-between;
}

/* =========================
   LOGO
========================= */
.header-logo{
    display:flex;
    align-items:center;
    height:100%;
}

.header-logo img{
    height:56px;
    width:auto;
    max-width:220px;
    object-fit:contain;
}

/* =========================
   ICON GROUPS
========================= */
.header-icons{
    display:flex;
    align-items:center;
    gap:18px;
}

/* COMMON ICON */
.icon-btn{
    color:#ffffff;
    font-size:1.25rem;
    position:relative;
    transition:.25s ease;
}

.icon-btn:hover{
    color:#e5e5e5;
}

.cart-badge{
    position:absolute;
    top:-6px;
    right:-10px;
    background:#ffffff;
    color:#000;
    font-size:10px;
    padding:2px 6px;
    border-radius:50%;
    font-weight:600;
}

/* =========================
   DESKTOP MENU
========================= */
.desktop-menu{
    display:none;
}

@media(min-width:992px){
    .desktop-menu{
        display:flex;
        gap:26px;
    }

    .desktop-menu a{
        color:#eaeaea;
        font-size:14px;
        font-weight:500;
        text-decoration:none;
        position:relative;
        padding-bottom:4px;
    }

    .desktop-menu a::after{
        content:'';
        position:absolute;
        left:0;
        bottom:0;
        width:0;
        height:2px;
        background:#ffffff;
        transition:.25s ease;
    }

    .desktop-menu a:hover::after,
    .desktop-menu a.active::after{
        width:100%;
    }

    .desktop-menu a.active{
        color:#ffffff;
    }
}

/* =========================
   ICON VISIBILITY RULES
========================= */

/* Mobile default */
.icon-desktop{display:none;}
.icon-mobile{display:inline-flex;}

/* Desktop */
@media(min-width:992px){
    .icon-desktop{display:inline-flex;}
    .icon-mobile{display:none;}
}

/* =========================
   MOBILE SIZE FIX
========================= */
@media(max-width:991px){
    .header-bar{height:64px;}
    body{padding-top:64px;}

    .header-logo img{
        height:48px;
        max-width:180px;
    }

    .icon-btn{
        font-size:1.2rem;
    }
}
</style>

<header class="header-bar">
    <div class="header-inner">

        <!-- LOGO -->
        <a href="index.php" class="header-logo">
            <img src="/proglide/image/pro-logo.png" alt="PROGLIDE">
        </a>

        <!-- DESKTOP MENU -->
        <nav class="desktop-menu">
            <a href="index.php" class="<?= $current=='index.php'?'active':'' ?>">Home</a>
            <a href="products.php" class="<?= $current=='products.php'?'active':'' ?>">Products</a>

            <?php if($user_id): ?>
                <a href="orders.php">Orders</a>
                <a href="wishlist.php">Wishlist</a>
            <?php endif; ?>

            <a href="help.php">Help</a>
        </nav>

        <!-- ICONS -->
        <div class="header-icons">

            <!-- MOBILE ONLY : HELP -->
            <a href="help.php" class="icon-btn icon-mobile" title="Help">
                <i class="fas fa-question-circle"></i>
            </a>

            <!-- DESKTOP ONLY : CART -->
            <a href="cart.php" class="icon-btn icon-desktop" title="Cart">
                <i class="fas fa-shopping-cart"></i>
                <?php if($cart_count>0): ?>
                    <span class="cart-badge"><?= $cart_count ?></span>
                <?php endif; ?>
            </a>

            <!-- DESKTOP ONLY : PROFILE / LOGIN -->
            <?php if($user_id): ?>
                <a href="profile.php" class="icon-btn icon-desktop" title="Profile">
                    <i class="fas fa-user"></i>
                </a>
            <?php else: ?>
                <a href="login.php" class="icon-btn icon-desktop" title="Login">
                    <i class="fas fa-sign-in-alt"></i>
                </a>
            <?php endif; ?>

        </div>

    </div>
</header>
