<?php
include "../includes/admin_auth.php";
include "../includes/admin_db.php";

/* Fetch all orders */
$sql = "
SELECT 
    o.*,
    u.name AS user_name,
    u.email
FROM orders o
JOIN users u ON u.id = o.user_id
ORDER BY o.created_at DESC
";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
<title>Orders | Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
:root{
    --bg:#020617;
    --card:#111827;
    --border:#374151;

    --primary:#6366f1;
    --info:#38bdf8;
    --success:#22c55e;
    --warning:#f59e0b;
    --danger:#ef4444;

    --text:#f9fafb;
    --muted:#9ca3af;
}

*{margin:0;padding:0;box-sizing:border-box}

body{
    font-family:Poppins,sans-serif;
    background:radial-gradient(circle at top,#020617,#000);
    color:var(--text);
}

/* CONTAINER */
.container{
    max-width:100%;
    padding-left:280px;
    
}
@media(max-width:992px){
    .container{padding-left:16px}
}

/* CARD */
.card{
    background:var(--card);
    border:1px solid var(--border);
    border-radius:18px;
    padding:16px;
    box-shadow:0 15px 35px rgba(0,0,0,.45);
}

/* TABLE */
.table{
    margin-bottom:0;
    color:var(--text);
    
}
.table th{
    background:#020617;
    color:var(--muted);
    font-size:12px;
    text-transform:uppercase;
    white-space:nowrap;
}
.table td{
    border-color:var(--border);
    white-space:nowrap;
    background-color: transparent;
    color: var(--text);
}
.table tbody tr:hover{
    background:rgba(255,255,255,.05);
}

/* BADGE */
.badge{
    font-size:11px;
    padding:6px 12px;
    border-radius:999px;
    font-weight:600;
}

/* BUTTON */
.btn-outline-light{
    border-color:var(--border);
    color:var(--text);
}
.btn-outline-light:hover{
    background:var(--primary);
    border-color:var(--primary);
    color:#fff;
}

/* FILTER */
.form-select{
    background:#020617;
    border:1px solid var(--border);
    color:var(--text);
}

/* ===============================
   MOBILE HORIZONTAL SCROLL
================================ */
.order-table-scroll{
    width:100%;
}

@media(max-width:768px){

    .order-table-scroll{
        overflow-x:auto;
        -webkit-overflow-scrolling:touch;
        border-radius:14px;
    }

    .order-table-scroll table{
        min-width:900px; /* 👈 KEY LINE */
    }

    .order-table-scroll::-webkit-scrollbar{
        height:6px;
    }

    .order-table-scroll::-webkit-scrollbar-thumb{
        background:#374151;
        border-radius:10px;
    }
}
</style>
</head>

<body>

<?php include "../includes/sidebar.php"; ?>
<?php include "../includes/header.php"; ?>

<div class="container py-4">

<!-- HEADER -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
    <h3 style="color:var(--primary)">Orders</h3>

    <div class="d-flex gap-2">
        <select id="categoryFilter" class="form-select">
            <option value="">All</option>
            <option value="pending">Pending</option>
            <option value="processing">Processing</option>
            <option value="shipped">Shipped</option>
            <option value="delivered">Delivered</option>
            <option value="cancelled">Cancelled</option>
        </select>

        <a href="../dashboard.php" class="btn btn-outline-light btn-sm">
            ← Dashboard
        </a>
    </div>
</div>

<!-- TABLE CARD -->
<div class="card">

<div class="order-table-scroll">
<table class="table table-bordered align-middle">
<thead>
<tr>
    <th>Order ID</th>
    <th>User</th>
    <th>Email</th>
    <th>Total</th>
    <th>order Status</th>
    <th>PY Status</th>
    <th>Date</th>
    <th>Action</th>
</tr>
</thead>
<tbody>

<?php if($result && $result->num_rows): ?>
<?php while($row=$result->fetch_assoc()):
$statusKey = strtolower($row['status']);
$color = match($row['status']){
    'Pending'=>'warning',
    'Processing'=>'info',
    'Shipped'=>'primary',
    'Delivered'=>'success',
    'Cancelled'=>'danger',
    default=>'secondary'
};
?>
<tr data-status="<?= $statusKey ?>">
    <td>#<?= $row['id'] ?></td>
    <td><?= htmlspecialchars($row['user_name']) ?></td>
    <td><?= htmlspecialchars($row['email']) ?></td>
    <td>₹<?= $row['total_amount'] ?></td>
    <td><span class="badge bg-<?= $color ?>"><?= $row['status'] ?></span></td>
    <td><?= date("d M Y", strtotime($row['created_at'])) ?></td>
    <td>
        <a href="order_view.php?id=<?= $row['id'] ?>"
           class="btn btn-sm btn-outline-light">
           View
        </a>
    </td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr>
    <td colspan="7" class="text-center">No orders found</td>
</tr>
<?php endif; ?>

</tbody>
</table>
</div>

</div>
</div>

<script>
document.getElementById("categoryFilter").addEventListener("change",function(){
    const val=this.value;
    document.querySelectorAll("tbody tr").forEach(row=>{
        row.style.display=(val===""||row.dataset.status===val)?"":"none";
    });
});
</script>

</body>
</html>
