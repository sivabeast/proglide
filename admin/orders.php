<?php
include "includes/admin_db.php";
include "includes/admin_auth.php";

/* ADMIN AUTH CHECK HERE */

$orders = $conn->query("
SELECT o.*, u.name 
FROM orders o
JOIN users u ON u.id = o.user_id
ORDER BY o.id DESC
");
?>
<!DOCTYPE html>
<html>

<head>
    <title>Admin Orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
    /* =====================================================
       ROOT VARIABLES (DARK ADMIN THEME)
    ===================================================== */
    :root {
        --primary: #8b5cf6;
        --secondary: #38bdf8;
        --bg: #020617;
        --card: #0f172a;
        --card-soft: #1e293b;
        --border: #334155;
        --text: #e5e7eb;
        --muted: #9ca3af;
        --success: #22c55e;
        --warning: #f59e0b;
        --danger: #ef4444;
    }

    /* =====================================================
       RESET
    ===================================================== */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        background: linear-gradient(180deg, #020617, #020617 60%, #020617);
        font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        color: var(--text);
        min-height: 100vh;
        margin-left: 260px;
    }

    /* =====================================================
       CONTAINER
    ===================================================== */
    .container-main {
        max-width: 1400px;
        margin: 0 auto;
        padding: 25px 25px 40px 25px;
        margin-top: 64px;
    }

    /* =====================================================
       PAGE TITLE
    ===================================================== */
    .page-title {
        font-size: 26px;
        font-weight: 700;
        color: #ffffff;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .page-title i {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        font-size: 28px;
    }

    /* =====================================================
       CARD WRAPPER
    ===================================================== */
    .card-wrapper {
        background: var(--card);
        border-radius: 20px;
        padding: 0;
        overflow: hidden;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.45);
        border: 1px solid rgba(255, 255, 255, 0.05);
    }

    /* =====================================================
       TABLE HEADER
    ===================================================== */
    .table-header {
        background: linear-gradient(180deg, #1e293b, #020617);
        padding: 20px 25px;
        border-bottom: 1px solid var(--border);
    }

    .table-title {
        font-size: 18px;
        font-weight: 600;
        color: #c7d2fe;
        margin: 0;
    }

    /* =====================================================
       TABLE
    ===================================================== */
    .table-responsive {
        padding: 0;
    }

    .table {
        margin: 0;
        background: transparent;
        border-collapse: separate;
        border-spacing: 0;
        width: 100%;
    }

    .table thead th {
        background: rgba(30, 41, 59, 0.5);
        color: #94a3b8;
        font-size: 13px;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        padding: 18px 20px;
        border: none;
        white-space: nowrap;
        border-bottom: 1px solid var(--border);
    }

    .table tbody tr {
        background: transparent;
        transition: all 0.25s ease;
        border-bottom: 1px solid rgba(255, 255, 255, 0.03);
    }

    .table tbody tr:last-child {
        border-bottom: none;
    }

    .table tbody tr:hover {
        background: rgba(139, 92, 246, 0.05);
    }

    .table tbody td {
        padding: 18px 20px;
        font-size: 14px;
        color: var(--text);
        vertical-align: middle;
        white-space: nowrap;
        border: none;
    }

    /* =====================================================
       PAYMENT METHOD
    ===================================================== */
    .payment-method {
        font-weight: 600;
        color: var(--primary);
        background: rgba(139, 92, 246, 0.1);
        padding: 6px 14px;
        border-radius: 8px;
        display: inline-block;
    }

    /* =====================================================
       PROOF LINK
    ===================================================== */
    .proof-link {
        color: #38bdf8;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.3s ease;
    }

    .proof-link:hover {
        color: #60a5fa;
        transform: translateX(2px);
    }

    /* =====================================================
       BADGES
    ===================================================== */
    .status-badge {
        padding: 8px 16px;
        font-size: 12px;
        border-radius: 10px;
        font-weight: 600;
        letter-spacing: 0.5px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .badge-paid {
        background: rgba(34, 197, 94, 0.15);
        color: #22c55e;
        border: 1px solid rgba(34, 197, 94, 0.3);
    }

    .badge-pending {
        background: rgba(245, 158, 11, 0.15);
        color: #f59e0b;
        border: 1px solid rgba(245, 158, 11, 0.3);
    }

    .badge-rejected {
        background: rgba(239, 68, 68, 0.15);
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.3);
    }

    /* =====================================================
       ACTION BUTTONS
    ===================================================== */
    .action-buttons {
        display: flex;
        gap: 10px;
    }

    .btn-action {
        padding: 8px 18px;
        font-size: 13px;
        font-weight: 600;
        border-radius: 10px;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .btn-approve {
        background: linear-gradient(135deg, #22c55e, #16a34a);
        color: white;
        box-shadow: 0 5px 15px rgba(34, 197, 94, 0.3);
    }

    .btn-approve:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(34, 197, 94, 0.4);
    }

    .btn-reject {
        background: linear-gradient(135deg, #ef4444, #b91c1c);
        color: white;
        box-shadow: 0 5px 15px rgba(239, 68, 68, 0.3);
    }

    .btn-reject:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4);
    }

    /* =====================================================
       AMOUNT STYLING
    ===================================================== */
    .amount {
        font-weight: 700;
        font-size: 15px;
        color: #ffffff;
    }

    /* =====================================================
       EMPTY STATE
    ===================================================== */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: var(--muted);
    }

    .empty-state i {
        font-size: 48px;
        margin-bottom: 20px;
        opacity: 0.5;
    }

    /* =====================================================
       RESPONSIVE – TABLET
    ===================================================== */
    @media (max-width: 992px) {
        body {
            margin-left: 0;
        }
        
        .container-main {
            padding: 20px 15px;
            margin-top: 80px;
        }
        
        .table tbody td {
            padding: 15px;
        }
    }

    /* =====================================================
       RESPONSIVE – MOBILE (CARD VIEW)
    ===================================================== */
    @media (max-width: 768px) {
        .table-header {
            padding: 15px;
        }
        
        .table thead {
            display: none;
        }
        
        .table tbody tr {
            display: block;
            background: linear-gradient(180deg, #0f172a, #020617);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            border: 1px solid var(--border);
        }
        
        .table tbody td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .table tbody td:last-child {
            border-bottom: none;
        }
        
        .table tbody td::before {
            content: attr(data-label);
            font-size: 12px;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .action-buttons {
            justify-content: center;
            margin-top: 15px;
        }
    }

    /* =====================================================
       MOBILE SIDEBAR ADJUSTMENT
    ===================================================== */
    @media (max-width: 768px) {
        .container-main {
            padding-top: 90px;
        }
    }
    </style>
</head>

<body>
    <?php include "includes/sidebar.php"; ?>
    <?php include "includes/header.php"; ?>

    <div class="container-main">
        <!-- Page Title -->
        <div class="page-title">
            <i class="bi bi-bag-check"></i>
            <span>Order Management</span>
        </div>

        <!-- Orders Card -->
        <div class="card-wrapper">
            <!-- Table Header -->
            <div class="table-header">
                <h5 class="table-title">Recent Orders</h5>
            </div>

            <!-- Orders Table -->
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ORDER ID</th>
                            <th>CUSTOMER</th>
                            <th>AMOUNT</th>
                            <th>PAYMENT</th>
                            <th>PROOF</th>
                            <th>STATUS</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($orders->num_rows > 0): ?>
                            <?php while ($o = $orders->fetch_assoc()): ?>
                                <tr>
                                    <td data-label="Order ID">#<?= $o['id'] ?></td>
                                    <td data-label="Customer"><?= htmlspecialchars($o['name']) ?></td>
                                    <td data-label="Amount" class="amount">₹<?= number_format($o['total_amount'], 2) ?></td>
                                    <td data-label="Payment">
                                        <span class="payment-method">
                                            <?= strtoupper($o['payment_method']) ?>
                                        </span>
                                    </td>
                                    <td data-label="Proof">
                                        <?php if ($o['payment_proof']): ?>
                                            <a href="/proglide/public/uploads/payments/<?= $o['payment_proof'] ?>" 
                                               target="_blank" 
                                               class="proof-link">
                                                <i class="bi bi-eye"></i>
                                                View Proof
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Status">
                                        <?php if ($o['payment_status'] == 'paid'): ?>
                                            <span class="status-badge badge-paid">
                                                <i class="bi bi-check-circle"></i>
                                                PAID
                                            </span>
                                        <?php elseif ($o['payment_status'] == 'rejected'): ?>
                                            <span class="status-badge badge-rejected">
                                                <i class="bi bi-x-circle"></i>
                                                REJECTED
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge badge-pending">
                                                <i class="bi bi-clock"></i>
                                                PENDING
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Actions">
                                        <?php if ($o['payment_status'] == 'pending' && $o['payment_method'] == 'upi'): ?>
                                            <div class="action-buttons">
                                                <a href="verify_payment.php?id=<?= $o['id'] ?>&status=paid" 
                                                   class="btn-action btn-approve">
                                                    <i class="bi bi-check-lg"></i>
                                                    Approve
                                                </a>
                                                <a href="verify_payment.php?id=<?= $o['id'] ?>&status=rejected"
                                                   class="btn-action btn-reject">
                                                    <i class="bi bi-x-lg"></i>
                                                    Reject
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <i class="bi bi-inbox"></i>
                                        <h5>No orders found</h5>
                                        <p>All orders will appear here</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>