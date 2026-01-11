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

<!-- Bootstrap & Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<style>
/* =========================
   GLOBAL
========================= */
body{
    padding-top:80px;
    
}

/* =========================
   HEADER BAR
========================= */
.header-bar{
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:60px;
    background:#0d0d0d;
    border-bottom:1px solid #2e2e2e;
    z-index:3000;
    
}
/* =========================
   LOGO FIX
========================= */
.header-logo{
    display:flex;
    align-items:center;
    height:100%;
}

.header-logo img{
    height:42px;          /* mobile default */
    width:auto;
    object-fit:contain;
}

.header-logo{
    font-family:'Montserrat', sans-serif;
    font-size:1.7rem;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:1.5px;
    color:#f59c1f;
    text-decoration:none;
}

.header-logo span{color:#4a6fa5}


/* Desktop */
@media(min-width:992px){
    .header-logo img{
        height:50px;
    }
}
.header-inner{
    display:flex;
    align-items:center;
    justify-content:space-between;
    height:100%;
    padding:0 14px;
}


/* ICONS */
.header-icons{
    display:flex;
    align-items:center;
    gap:18px;
}

.icon-btn{
    color:#fff;
    font-size:1.25rem;
    position:relative;
}
.icon-btn:hover{color:#4a6fa5}

.cart-badge{
    position:absolute;
    top:-6px;
    right:-10px;
    background:#4a6fa5;
    color:#fff;
    font-size:10px;
    padding:2px 6px;
    border-radius:50%;
}

/* =========================
   DESKTOP MENU
========================= */
.desktop-menu{
    display:none;
}

@media(min-width:992px){
    body{padding-top:70px}

    .header-bar{height:70px}

    .header-inner{
        max-width:1200px;
        margin:auto;
    }

    .desktop-menu{
        display:flex;
        gap:22px;
    }

    .desktop-menu a{
        color:#eaeaea;
        font-weight:500;
        text-decoration:none;
    }

    .desktop-menu a:hover,
    .desktop-menu a.active{
        color:#f59c1f;
    }
}
</style>

<!-- =========================
   HEADER
========================= -->
<header class="header-bar">
    <div class="header-inner">

        <!-- LEFT : LOGO -->
        <a href="index.php" class="header-logo">
            <img src="/proglide/image/logo.png" alt="protectors">
            <h4>PROTECT<span>ORS</span></h4>
        </a>

        <!-- CENTER : DESKTOP MENU ONLY -->
        <nav class="desktop-menu">
            <a href="index.php" class="<?= $current=='index.php'?'active':'' ?>">Home</a>
            <a href="products.php" class="<?= $current=='products.php'?'active':'' ?>">Products</a>

            <?php if($user_id): ?>
                <a href="orders.php">Orders</a>
                <a href="wishlist.php">Wishlist</a>
            <?php endif; ?>

            <a href="help.php">Help</a>
        </nav>

        <!-- RIGHT : ICONS (MOBILE + DESKTOP) -->
        <div class="header-icons">

            <!-- HELP -->
            <a href="help.php" class="icon-btn" title="Help">
                <i class="fas fa-question-circle"></i>
            </a>

            <!-- CART -->
            <a href="cart.php" class="icon-btn" title="Cart">
                <i class="fas fa-shopping-cart"></i>
                <?php if($cart_count>0): ?>
                    <span class="cart-badge"><?= $cart_count ?></span>
                <?php endif; ?>
            </a>

            <!-- PROFILE / LOGIN (DESKTOP ONLY FEEL, STILL OK ON MOBILE) -->
            <?php if($user_id): ?>
                <a href="profile.php" class="icon-btn" title="Profile">
                    <i class="fas fa-user"></i>
                </a>
            <?php else: ?>
                <a href="login.php" class="icon-btn" title="Login">
                    <i class="fas fa-sign-in-alt"></i>
                </a>
            <?php endif; ?>

        </div>

    </div>
</header>
