<?php
session_start();
require "includes/db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total orders count
$count_query = "SELECT COUNT(*) as total FROM orders WHERE user_id = ?";
$stmt = $conn->prepare($count_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_orders = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_orders / $limit);

// Get user orders
$query = "SELECT o.*, 
          (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count,
          (SELECT SUM(quantity) FROM order_items WHERE order_id = o.id) as total_items
          FROM orders o 
          WHERE o.user_id = ? 
          ORDER BY o.created_at DESC 
          LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $user_id, $limit, $offset);
$stmt->execute();
$orders = $stmt->get_result();

// Get user details
$user_query = "SELECT name, email, phone FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get order status colors
function getStatusColor($status) {
    switch($status) {
        case 'Pending':
            return 'warning';
        case 'Processing':
            return 'info';
        case 'Delivered':
            return 'success';
        case 'Cancelled':
            return 'danger';
        default:
            return 'secondary';
    }
}

function getStatusIcon($status) {
    switch($status) {
        case 'Pending':
            return 'fa-clock';
        case 'Processing':
            return 'fa-spinner fa-spin';
        case 'Delivered':
            return 'fa-check-circle';
        case 'Cancelled':
            return 'fa-times-circle';
        default:
            return 'fa-circle';
    }
}

function getPaymentColor($status) {
    switch($status) {
        case 'paid':
            return 'success';
        case 'pending':
            return 'warning';
        case 'failed':
            return 'danger';
        default:
            return 'secondary';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders | PROGLIDE</title>
    <link rel="icon" type="image/x-icon" href="/proglide/image/logo.png">
    
    <!-- Bootstrap & Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-top: 100px;
        }

        .orders-container {
            max-width: 1200px;
            margin: 0 auto 50px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .page-header h2 {
            font-size: 28px;
            font-weight: 700;
            color: #333;
        }

        .page-header h2 i {
            color: #FF6B35;
            margin-right: 10px;
        }

        .user-info-card {
            background: linear-gradient(135deg, #FF6B35, #FF8E53);
            color: white;
            border-radius: 16px;
            padding: 20px 30px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 10px 25px rgba(255, 107, 53, 0.3);
        }

        .user-avatar {
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: 700;
        }

        .user-info h4 {
            font-size: 18px;
            margin-bottom: 5px;
            opacity: 0.9;
        }

        .user-info p {
            font-size: 20px;
            font-weight: 700;
            margin: 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: #fff5f2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #FF6B35;
            font-size: 22px;
        }

        .stat-info h3 {
            font-size: 24px;
            font-weight: 700;
            margin: 0;
            color: #333;
        }

        .stat-info p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }

        .orders-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .orders-header {
            padding: 20px 25px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .orders-header h5 {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin: 0;
        }

        .orders-header h5 i {
            color: #FF6B35;
            margin-right: 8px;
        }

        .search-box {
            position: relative;
            width: 300px;
        }

        .search-box input {
            width: 100%;
            padding: 10px 15px 10px 40px;
            border: 2px solid #eee;
            border-radius: 50px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: #FF6B35;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 15px 20px;
            background: #f8f9fa;
            color: #555;
            font-weight: 600;
            font-size: 14px;
        }

        td {
            padding: 20px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background: #f8f9fa;
        }

        .order-id {
            font-weight: 700;
            color: #FF6B35;
            text-decoration: none;
        }

        .order-id:hover {
            text-decoration: underline;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-secondary {
            background: #e2e3e5;
            color: #383d41;
        }

        .amount {
            font-weight: 700;
            color: #28a745;
        }

        .action-btn {
            padding: 8px 15px;
            border-radius: 8px;
            border: none;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-view {
            background: #e7f3ff;
            color: #0066cc;
        }

        .btn-view:hover {
            background: #0066cc;
            color: white;
        }

        .btn-track {
            background: #fff3cd;
            color: #856404;
        }

        .btn-track:hover {
            background: #856404;
            color: white;
        }

        .btn-invoice {
            background: #e2e3e5;
            color: #383d41;
        }

        .btn-invoice:hover {
            background: #383d41;
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state i {
            font-size: 60px;
            color: #ddd;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 22px;
            color: #333;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #666;
            margin-bottom: 25px;
        }

        .btn-shop {
            background: linear-gradient(135deg, #FF6B35, #FF8E53);
            color: white;
            padding: 12px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }

        .btn-shop:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 107, 53, 0.3);
            color: white;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 30px;
        }

        .page-item {
            list-style: none;
        }

        .page-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            color: #555;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .page-link:hover,
        .page-item.active .page-link {
            background: #FF6B35;
            color: white;
            border-color: #FF6B35;
        }

        .page-item.disabled .page-link {
            opacity: 0.5;
            pointer-events: none;
        }

        @media (max-width: 991px) {
            body {
                padding-top: 80px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .user-info-card {
                width: 100%;
            }

            .search-box {
                width: 100%;
            }

            td {
                padding: 15px;
            }

            .action-btn span {
                display: none;
            }

            .action-btn i {
                font-size: 16px;
            }

            .action-btn {
                padding: 8px 12px;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .user-info-card {
                flex-direction: column;
                text-align: center;
            }

            .orders-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include "includes/header.php"; ?>

    <div class="container orders-container">
        <!-- Page Header -->
        <div class="page-header">
            <h2><i class="fas fa-box-open"></i> My Orders</h2>
            
            <div class="user-info-card">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($user['name'] ?? 'U', 0, 1)); ?>
                </div>
                <div class="user-info">
                    <h4>Welcome back,</h4>
                    <p><?php echo htmlspecialchars($user['name'] ?? 'User'); ?></p>
                </div>
            </div>
        </div>

        <?php
        // Calculate statistics
        $stats_query = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'Processing' THEN 1 ELSE 0 END) as processing,
            SUM(CASE WHEN status = 'Delivered' THEN 1 ELSE 0 END) as delivered,
            SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled,
            SUM(total_amount) as total_spent
            FROM orders WHERE user_id = ?";
        $stmt = $conn->prepare($stats_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stats = $stmt->get_result()->fetch_assoc();
        ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['total'] ?? 0; ?></h3>
                    <p>Total Orders</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['pending'] ?? 0; ?></h3>
                    <p>Pending</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['delivered'] ?? 0; ?></h3>
                    <p>Delivered</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-rupee-sign"></i>
                </div>
                <div class="stat-info">
                    <h3>₹<?php echo number_format($stats['total_spent'] ?? 0, 2); ?></h3>
                    <p>Total Spent</p>
                </div>
            </div>
        </div>

        <!-- Orders List -->
        <div class="orders-card">
            <div class="orders-header">
                <h5><i class="fas fa-list"></i> Order History</h5>
                
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchOrders" placeholder="Search orders...">
                </div>
            </div>

            <?php if ($orders && $orders->num_rows > 0): ?>
                <div class="table-responsive">
                    <table id="ordersTable">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($order = $orders->fetch_assoc()): ?>
                                <tr class="order-row">
                                    <td>
                                        <a href="order_details.php?id=<?php echo $order['id']; ?>" class="order-id">
                                            #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php echo date('d M Y', strtotime($order['created_at'])); ?>
                                        <br>
                                        <small class="text-muted"><?php echo date('h:i A', strtotime($order['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge badge-secondary">
                                            <i class="fas fa-box"></i> <?php echo $order['total_items'] ?? $order['item_count']; ?> items
                                        </span>
                                    </td>
                                    <td>
                                        <span class="amount">₹<?php echo number_format($order['total_amount'], 2); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo getPaymentColor($order['payment_status']); ?>">
                                            <i class="fas fa-<?php echo $order['payment_status'] == 'paid' ? 'check-circle' : 'clock'; ?>"></i>
                                            <?php echo ucfirst($order['payment_status']); ?>
                                        </span>
                                        <?php if (!empty($order['payment_method'])): ?>
                                            <br>
                                            <small class="text-muted"><?php echo $order['payment_method'] == 'upi' ? 'UPI' : 'COD'; ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo getStatusColor($order['status']); ?>">
                                            <i class="fas <?php echo getStatusIcon($order['status']); ?>"></i>
                                            <?php echo $order['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 5px;">
                                            <a href="order_details.php?id=<?php echo $order['id']; ?>" class="action-btn btn-view" title="View Details">
                                                <i class="fas fa-eye"></i>
                                                <span>View</span>
                                            </a>
                                            <?php if ($order['status'] == 'Pending'): ?>
                                                <a href="track_order.php?id=<?php echo $order['id']; ?>" class="action-btn btn-track" title="Track Order">
                                                    <i class="fas fa-truck"></i>
                                                    <span>Track</span>
                                                </a>
                                            <?php endif; ?>
                                            <a href="invoice.php?id=<?php echo $order['id']; ?>" class="action-btn btn-invoice" title="Download Invoice" target="_blank">
                                                <i class="fas fa-file-pdf"></i>
                                                <span>Invoice</span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <h3>No Orders Yet</h3>
                    <p>Looks like you haven't placed any orders yet. Start shopping!</p>
                    <a href="products.php" class="btn-shop">
                        <i class="fas fa-shopping-bag"></i> Continue Shopping
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <ul class="pagination">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a href="?page=<?php echo $page - 1; ?>" class="page-link">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
                
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                        <a href="?page=<?php echo $i; ?>" class="page-link"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                
                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                    <a href="?page=<?php echo $page + 1; ?>" class="page-link">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            </ul>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <?php include "includes/footer.php"; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Search functionality
        document.getElementById('searchOrders').addEventListener('keyup', function() {
            let searchText = this.value.toLowerCase();
            let rows = document.querySelectorAll('.order-row');
            
            rows.forEach(row => {
                let text = row.textContent.toLowerCase();
                if (text.includes(searchText)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Auto-hide alerts
        setTimeout(function() {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>
<?php $conn->close(); ?>