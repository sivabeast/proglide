<?php
$page = basename($_SERVER['PHP_SELF']);
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

<style>
    :root {
        --primary: #8b5cf6;
        --primary-dark: #7c3aed;
        --bg: #ffffffff;
        --sidebar: #000000ff;
        --text: #cbd5e1;
    }

    /* ===== SIDEBAR BASE ===== */
    .sidebar {
        width: 260px;
        background: linear-gradient(180deg, #000000ff, #000000ff);
        position: fixed;
        top: 0;
        left: 0;
        bottom: 0;
        padding: 22px 18px;
        z-index: 1000;
        transition: all .35s ease;
        box-shadow: 6px 0 30px rgba(0, 0, 0, .45);
    }

    /* Brand */
    .sidebar h3 {
        font-weight: 800;
        letter-spacing: 1px;
        margin-bottom: 30px;
        text-align: center;
        color: var(--text);
    }

    .sidebar h3 span {
        background: linear-gradient(90deg, var(--primary), #4A70A9);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    /* Menu */
    .sidebar ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .sidebar li {
        margin-bottom: 6px;
    }

    /* Links */
    .sidebar a {
        position: relative;
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 13px 14px;
        border-radius: 12px;
        color: var(--text);
        text-decoration: none;
        font-size: 15px;
        font-weight: 500;
        transition: .35s ease;
        overflow: hidden;
    }

    /* Hover */
    .sidebar a:hover {
        background: rgba(139, 92, 246, .12);
        color: #fff;
        transform: translateX(6px);
    }

    /* Active */
    .sidebar a.active {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: #fff;
        box-shadow: 0 10px 25px rgba(139, 92, 246, .45);
    }

    .sidebar a.active::before {
        content: "";
        position: absolute;
        left: 0;
        top: 15%;
        height: 70%;
        width: 4px;
        background: #fff;
        border-radius: 4px;
        animation: activePulse 1.5s infinite;
    }

    @keyframes activePulse {
        0% {
            opacity: .4
        }

        50% {
            opacity: 1
        }

        100% {
            opacity: .4
        }
    }

    /* Icons */
    .sidebar i {
        font-size: 18px;
        min-width: 22px;
    }

    /* Logout */
    .sidebar a.logout {
        color: #f87171;
    }

    .sidebar a.logout:hover {
        background: rgba(248, 113, 113, .15);
        color: #fff;
    }

    /* ===== MOBILE ===== */
    @media(max-width:992px) {
        .sidebar {
            left: -280px;
        }

        .sidebar.show {
            left: 0;
        }

        .sidebar-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
        }
    }

    @media(max-height:500px) {
        .sidebar {
            overflow-y: auto;
        }
    }

    /* Overlay */
    .sidebar-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, .6);
        z-index: 399;
    }

    .sidebar-overlay.show {
        display: block;
    }

    /* ===== TOGGLE BUTTON ===== */
    .sidebar-toggle {
        display: none;
        position: fixed;
        top: 10px;
        left: 15px;
        z-index: 1000;
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        border: none;
        width: 45px;
        height: 45px;
        border-radius: 12px;
        color: #fff;
        font-size: 22px;
        box-shadow: 0 10px 25px rgba(139, 92, 246, .45);
    }

    /* Hide toggle when sidebar open */
    .sidebar-open .sidebar-toggle {
        display: none !important;
    }

    @media(max-width:992px) {
        .sidebar-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
        }
    }
</style>

<!-- MOBILE TOGGLE -->
<button class="sidebar-toggle" onclick="toggleSidebar()">
    <i class="bi bi-list"></i>
</button>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<aside class="sidebar" id="sidebar">

    <h3>PROTECT<span>ORS</span></h3>

    <ul>

        <li>
            <a href="/proglide/admin/dashboard.php" class="<?= $page == 'dashboard.php' ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>


        <li>
            <a href="/proglide/admin/products/add_protector.php" class="<?= $page == 'add_protector.php' ? 'active' : '' ?>">
                <i class="bi bi-shield-check"></i> Protectors
            </a>
        </li>

        <li>
            <a href="/proglide/admin/products/add_backcase_design.php"
                class="<?= $page == 'add_backcase_design.php' ? 'active' : '' ?>">
                <i class="bi bi-phone"></i> Back Cases
            </a>
        </li>

        <li>
            <a href="/proglide/admin/manage_brands_models.php"
                class="<?= $page == 'manage_brands_models.php' ? 'active' : '' ?>">
                <i class="bi bi-building"></i> Brand & Models
            </a>
        </li>
        <li>
            <a href="/proglide/admin/orders.php" class="<?= $page == 'orders.php' ? 'active' : '' ?>">
                <i class="bi bi-bag-check"></i> payments
            </a>
        </li>
        <li>
            <a href="/proglide/admin/orders/orders_list.php" class="<?= $page == 'orders_list.php' ? 'active' : '' ?>">
                <i class="bi bi-bag-check"></i> Orders
            </a>
        </li>

        <li>
            <a href="/proglide/admin/users.php" class="<?= $page == 'users.php' ? 'active' : '' ?>">
                <i class="bi bi-people"></i> Users
            </a>
        </li>

        <li>
            <a href="/proglide/admin/profile.php" class="<?= $page == 'profile.php' ? 'active' : '' ?>">
                <i class="bi bi-person"></i> Profile
            </a>
        </li>

        <li>
            <a href="/proglide/admin/logout.php" class="logout">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </li>

    </ul>
</aside>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById("sidebar");
        const overlay = document.getElementById("sidebarOverlay");
        const body = document.body;

        sidebar.classList.toggle("show");
        overlay.classList.toggle("show");
        body.classList.toggle("sidebar-open");
    }
</script>