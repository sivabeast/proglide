<?php
include "includes/admin_auth.php";
include "includes/admin_db.php";

/* COUNTS */
$users = $conn->query("SELECT COUNT(*) total FROM users")->fetch_assoc()['total'];
$products = $conn->query("SELECT COUNT(*) total FROM products")->fetch_assoc()['total'];
$orders = $conn->query("SELECT COUNT(*) total FROM orders")->fetch_assoc()['total'];

/* MONTHLY REVENUE */
$monthlyRevenue = $conn->query("
    SELECT IFNULL(SUM(total_amount),0) revenue
    FROM orders
    WHERE MONTH(created_at)=MONTH(CURDATE())
    AND YEAR(created_at)=YEAR(CURDATE())
    AND status!='Cancelled'
")->fetch_assoc()['revenue'];

/* ORDER COUNTS */
$pendingOrders = $conn->query(
    "SELECT COUNT(*) total FROM orders WHERE status='Pending'"
)->fetch_assoc()['total'];

$processingOrders = $conn->query(
    "SELECT COUNT(*) total FROM orders WHERE status='Processing'"
)->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html>
<head>
<title>Admin Dashboard | PROTECTORS</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
/* =====================================================
   ROOT VARIABLES – CLEAN BLACK & WHITE
===================================================== */
:root{
    --bg: #0b0b0b;
    --bg-soft:#101010;
    --card:#151515;
    --card-hover:#1b1b1b;
    --border:#262626;

    --text:#f5f5f5;
    --muted:#b5b5b5;

    --white:#ffffff;
}

/* =====================================================
   RESET
===================================================== */
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    color: #fff;
}

body{
    font-family:'Poppins',sans-serif;
    /* background:linear-gradient(180deg, #000, #141414ff); */
    background: #0e0e0eff;
    color:var(--text);
    min-height:100vh;
    overflow-x:hidden;
}

/* =====================================================
   LAYOUT
===================================================== */
.admin-wrapper{
    display:flex;
    min-height:100vh;
}

.content{
    margin-left:260px;
    width:calc(100% - 260px);
    padding:28px;
}

@media(max-width:992px){
    .content{
        margin-left:0;
        width:100%;
        padding:18px 14px;
    }
}

/* =====================================================
   HEADER
===================================================== */
.dashboard-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:18px 22px;
    margin-bottom:26px;
    border-bottom:1px solid var(--border);
}

.dashboard-header h4{
    font-size:20px;
    font-weight:600;
    color:var(--white);
}

.dashboard-header .text-secondary{
    font-size:13px;
    color:var(--muted)!important;
}

/* =====================================================
   CARD BASE
===================================================== */
.card{
    background:linear-gradient(180deg,#161616,#0f0f0f);
    border:1px solid var(--border);
    border-radius:16px;
    padding:20px;
    transition:.3s ease;
    animation:fadeUp .45s ease both;
}

.card:hover{
    transform:translateY(-4px);
    background:linear-gradient(180deg,#1b1b1b,#121212);
}

/* =====================================================
   STAT CARDS
===================================================== */
.stat-card{
    text-align:center;
}

.stat-card i{
    font-size:22px;
    color:var(--white);
    margin-bottom:6px;
}

.stat-card h6{
    font-size:11px;
    
    text-transform:uppercase;
    color:var(--muted);
}

.stat-card h2{
    font-size:26px;
    font-weight:600;
    margin-top:6px;
    color:var(--white);
}

/* =====================================================
   REVENUE / PROCESSING
===================================================== */

.revenue-subtext{
    font-size:12px;
    color:var(--muted);
}
.revenue-card h2{
    color: #00ff00ff;;
}

.processing-card h2{
    color: #f36666ff;;
}
.progress{
    height:7px;
    background:#1a1a1a;
    border-radius:6px;
    overflow:hidden;
}

.progress-bar{
    background:#ffffff;
    animation:progressFill .9s ease;
}

/* =====================================================
   ORDERS TABLE CARD
===================================================== */
.orders-table{
    border-radius:16px;
    overflow:hidden;
    border:1px solid var(--border);
}

/* =====================================================
   TABLE STYLING
===================================================== */
.table-dark{
    --bs-table-bg:transparent;
    --bs-table-border-color:var(--border);
    color:var(--text);
}

.table-dark thead th{
    background:#0d0d0d;
    color:var(--muted);
    font-size:12px;
    font-weight:500;
    letter-spacing:.5px;
    text-transform:uppercase;
}

.table-dark tbody tr{
    border-bottom:1px solid var(--border);
    transition:.25s ease;
}

.table-dark tbody tr:hover{
    background:#161616;
}

.table-dark td{
    font-size:13px;
    padding:14px;
    vertical-align:middle;
}

/* =====================================================
   STATUS BADGES
===================================================== */
.status-badge{
    padding:5px 14px;
    font-size:11px;
    border-radius:999px;
    border:1px solid var(--border);
    color:var(--white);
}

.badge-pending{background:#1b1b1b;}
.badge-processing{background:#262626;}

/* =====================================================
   BUTTONS
===================================================== */
.btn{
    border-radius:10px;
    font-size:12px;
    padding:6px 14px;
}
.btn-view i{
    color:#000 !important;
}

.btn-warning,
.btn-primary{
    background:#ffffff;
    color:#000;
    border:none;
}

.btn-warning:hover,
.btn-primary:hover{
    background:#e5e5e5;
}

/* =====================================================
   LOADING SHIMMER
===================================================== */
.loading-shimmer{
    width:100%;
    background:linear-gradient(
        90deg,
        #1a1a1a 25%,
        #2a2a2a 37%,
        #1a1a1a 63%
    );
    background-size:400% 100%;
    animation:shimmer 1.3s infinite;
    border-radius:8px;
}

/* =====================================================
   MOBILE RESPONSIVE FIX (IMPORTANT)
===================================================== */
/* ===============================
   MOBILE – 2 CARDS PER ROW (FIXED)
================================ */
@media (max-width: 767px){

    /* Top stat cards + revenue cards → 2 per row */
    .cardd > .col-lg-3
    {
        flex: 0 0 50% !important;
        max-width: 50% !important;
    }

    /* Orders tables → full width */
    .orders-row > div{
        flex: 0 0 100% !important;
        max-width: 100% !important;
    }

    .dashboard-header{
        flex-direction: column;
        align-items: flex-start;
        gap: 6px;
    }
}


/* VERY SMALL MOBILE */
@media(max-width:480px){
    .row.g-4 > div{
        flex:0 0 100%;
        max-width:100%;
    }
    .content{
        padding:14px 10px;
    }
}

/* =====================================================
   SCROLLBAR
===================================================== */
::-webkit-scrollbar{
    width:6px;
}
::-webkit-scrollbar-thumb{
    background:#333;
    border-radius:10px;
}

/* =====================================================
   ANIMATIONS
===================================================== */
@keyframes fadeUp{
    from{opacity:0;transform:translateY(20px)}
    to{opacity:1;transform:none}
}

@keyframes shimmer{
    0%{background-position:0%}
    100%{background-position:100%}
}

@keyframes progressFill{
    from{width:0}
}

</style>

</head>

<body>
<?php include "includes/sidebar.php"; ?>
<?php include "includes/header.php"; ?>
<div class="admin-wrapper">

<!-- ===== MAIN CONTENT ===== -->
<main class="content">

<!-- HEADER -->
<div class="dashboard-header">
    <h4><i class="fas fa-tachometer-alt me-2"></i>Dashboard Overview</h4>
    <div class="text-secondary">
        <i class="fas fa-calendar-alt me-1"></i>
        <?php echo date('F j, Y'); ?>
    </div>
</div>

<!-- STATS CARDS -->
<div class="row g-4 cardd">

    <div class="col-lg-3 col-md-6">
        <div class="card stat-card">
            <i class="fas fa-users"></i>
            <h6>Total Users</h6>
            <h2><?= $users ?></h2>
            <div class="mt-3">
                <span class="text-success small">
                    <i class="fas fa-arrow-up me-1"></i>Active
                </span>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card stat-card">
            <i class="fas fa-box"></i>
            <h6>Total Products</h6>
            <h2><?= $products ?></h2>
            <div class="mt-3">
                <span class="text-info small">
                    <i class="fas fa-cube me-1"></i>In Stock
                </span>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card stat-card">
            <i class="fas fa-shopping-cart"></i>
            <h6>Total Orders</h6>
            <h2><?= $orders ?></h2>
            <div class="mt-3">
                <span class="text-warning small">
                    <i class="fas fa-chart-line me-1"></i>All Time
                </span>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card stat-card">
            <i class="fas fa-clock"></i>
            <h6>Pending Orders</h6>
            <h2 style="color: #fff"><?= $pendingOrders ?></h2>
            <div class="mt-0">
                <span class="text-warning small">
                    <i class="fas fa-exclamation-circle me-1"></i>Needs Attention
                </span>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card revenue-card">
            <div class="d-flex align-items-center mb-3">
                <div class="bg-success bg-opacity-10 p-3 rounded-circle me-3">
                    <i class="fas fa-rupee-sign fa-2x text-success"></i>
                </div>
                <div>
                    <h6 class="mb-1">This Month Revenue</h6>
                    <p class="small mb-0 revenue-subtext">
                            Total earnings this month</p>
                </div>
            </div>
            <h2 class="mb-0">
                ₹ <?= number_format($monthlyRevenue, 2) ?>
            </h2>
            <div class="progress mt-3" style="height: 8px;">
                <div class="progress-bar bg-success" role="progressbar" 
                     style="width: 85%" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card processing-card">
            <div class="d-flex align-items-center mb-3">
                <div class="bg-info bg-opacity-10 p-3 rounded-circle me-3" >
                    <i class="fas fa-cogs text-info"></i>
                </div>
                <div>
                    <h6 class="mb-1">Processing Orders</h6>
                    <p class="small mb-0 revenue-subtext">Currently being processed</p>
                </div>
            </div>
            <h2 class="mb-0" ><?= $processingOrders ?></h2>
            <div class="progress mt-3" style="height: 8px;">
                <div class="progress-bar bg-info" role="progressbar" 
                     style="width: 60%" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>
    </div>
</div>



<!-- ORDERS TABLES -->
<div class="row mt-4 g-4 orders-row">
    <div class="col-lg-6">
        <div class="card orders-table">
            <div class="card-header" style="color: #f96217ff">
                <i class="fas fa-clock"></i> Pending Orders
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>User</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="pendingOrdersBody">
                            <!-- Loading state -->
                            <tr>
                                <td colspan="6">
                                    <div class="loading-shimmer" style="height: 60px;"></div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card orders-table">
            <div class="card-header" style="color: #e8b030ff">
                <i class="fas fa-cogs"></i> Processing Orders
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>User</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="processingOrdersBody">
                            <!-- Loading state -->
                            <tr>
                                <td colspan="6">
                                    <div class="loading-shimmer" style="height: 60px;"></div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

</main>
</div>

<script>
// Enhanced JavaScript with animations
document.addEventListener('DOMContentLoaded', function() {
    // Add entrance animation to cards
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });
    
    // Load orders with loading animation
    loadDashboardOrders();
    
    // Auto-refresh every 30 seconds
    setInterval(loadDashboardOrders, 30000);
    
    // Add hover effect to table rows
    document.addEventListener('mouseover', function(e) {
        if (e.target.closest('tbody tr')) {
            e.target.closest('tbody tr').classList.add('hover-effect');
        }
    });
    
    document.addEventListener('mouseout', function(e) {
        if (e.target.closest('tbody tr')) {
            e.target.closest('tbody tr').classList.remove('hover-effect');
        }
    });
});

function loadDashboardOrders() {
    // Show loading shimmer
    const pendingBody = document.getElementById('pendingOrdersBody');
    const processingBody = document.getElementById('processingOrdersBody');
    
    pendingBody.innerHTML = `
        <tr>
            <td colspan="6">
                <div class="loading-shimmer" style="height: 60px;"></div>
            </td>
        </tr>
    `;
    
    processingBody.innerHTML = `
        <tr>
            <td colspan="6">
                <div class="loading-shimmer" style="height: 60px;"></div>
            </td>
        </tr>
    `;
    
    // Fetch data
    fetch("ajax/dashboard_orders.php")
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            let pendingHtml = '';
            let processingHtml = '';
            
            // Generate pending orders table rows
            if (data.pending && data.pending.length) {
                data.pending.forEach(order => {
                    pendingHtml += `
                    <tr>
                        <td><strong>#${order.id}</strong></td>
                        <td>${order.user_id || 'Guest'}</td>
                        <td>₹${parseFloat(order.total_amount).toFixed(2)}</td>
                        <td>${new Date(order.created_at).toLocaleDateString('en-IN')}</td>
                        <td><span class="status-badge badge-pending">Pending</span></td>
                        <td>
                            <a class="btn btn-sm btn-view btn-warning" 
                               href="orders/order_view.php?id=${order.id}">
                                <i class="fas fa-eye me-1"></i>View
                            </a>
                        </td>
                    </tr>
                    `;
                });
            } else {
                pendingHtml = `
                <tr>
                    <td colspan="6" class="text-center py-4">
                        <div class="text-muted">
                            <i class="fas fa-inbox fa-2x mb-2"></i>
                            <p class="mb-0">No pending orders</p>
                        </div>
                    </td>
                </tr>
                `;
            }
            
            // Generate processing orders table rows
            if (data.processing && data.processing.length) {
                data.processing.forEach(order => {
                    processingHtml += `
                    <tr>
                        <td><strong>#${order.id}</strong></td>
                        <td>${order.user_id || 'Guest'}</td>
                        <td>₹${parseFloat(order.total_amount).toFixed(2)}</td>
                        <td>${new Date(order.created_at).toLocaleDateString('en-IN')}</td>
                        <td><span class="status-badge badge-processing">Processing</span></td>
                        <td>
                            <a class="btn btn-sm btn-view btn-primary" 
                               href="orders/order_view.php?id=${order.id}">
                                <i class="fas fa-eye me-1"></i>View
                            </a>
                        </td>
                    </tr>
                    `;
                });
            } else {
                processingHtml = `
                <tr>
                    <td colspan="6" class="text-center py-4">
                        <div class="text-muted">
                            <i class="fas fa-cogs fa-2x mb-2"></i>
                            <p class="mb-0">No processing orders</p>
                        </div>
                    </td>
                </tr>
                `;
            }
            
            // Apply with fade-in animation
            pendingBody.innerHTML = pendingHtml;
            processingBody.innerHTML = processingHtml;
            
            // Add animation to new rows
            const newRows = document.querySelectorAll('#pendingOrdersBody tr, #processingOrdersBody tr');
            newRows.forEach((row, index) => {
                row.style.animation = `fadeIn 0.5s ease-out ${index * 0.05}s both`;
            });
        })
        .catch(error => {
            console.error('Error loading orders:', error);
            
            // Show error state
            pendingBody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-4 text-danger">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <p class="mb-0">Failed to load data</p>
                    <button class="btn btn-sm btn-outline-danger mt-2" onclick="loadDashboardOrders()">
                        <i class="fas fa-redo me-1"></i>Retry
                    </button>
                </td>
            </tr>
            `;
            
            processingBody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-4 text-danger">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <p class="mb-0">Failed to load data</p>
                    <button class="btn btn-sm btn-outline-danger mt-2" onclick="loadDashboardOrders()">
                        <i class="fas fa-redo me-1"></i>Retry
                    </button>
                </td>
            </tr>
            `;
        });
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>